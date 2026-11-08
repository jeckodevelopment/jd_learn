<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\User\User;
use Joomla\CMS\Workflow\Workflow;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

/** Serialises publication with authoring (course, then version). No remote I/O. */
final class PublicationService
{
    private ?array $permittedChange = null;
    private ?bool $stateChangeResult = null;

    public function __construct(private readonly DatabaseInterface $db, private readonly CMSApplicationInterface $app)
    {
    }

    private function workflow(): Workflow
    {
        return new Workflow('com_jlms.course', $this->app, $this->db);
    }

    private function row(string $table, int $id, bool $lock = false): object
    {
        $q = $this->db->createQuery()->select('*')->from($this->db->quoteName('#__jlms_' . $table))
            ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
        if ($lock) {
            $q->setQuery((string) $q . ' FOR UPDATE');
        }
        return $this->db->setQuery($q)->loadObject() ?: throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
    }

    private function fresh(User $identity): User
    {
        // Preserve the service's DomainException contract for denied publication.
        $actor = new User((int)$identity->id); $actor->clearAccessRights();
        return $actor;
    }

    private function authorise(User $actor, object $course): void
    {
        if (!$actor->id || $actor->block || (int) $actor->id !== (int) $this->app->getIdentity()->id
            || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('core.edit.state', 'com_jlms.course.' . (int) $course->id)) {
            throw new \DomainException('JERROR_ALERTNOAUTHOR');
        }
    }

    /** The core Publishing plugin calls CourseModel::publish only inside this guarded transition. */
    public function permitsStateChange(array $ids, int $value): bool
    {
        return $this->permittedChange !== null
            && array_map('intval', array_values($ids)) === [$this->permittedChange[0]]
            && $value === $this->permittedChange[1];
    }

    public function recordStateChangeResult(bool $success): void
    {
        if ($this->permittedChange !== null) {
            $this->stateChangeResult = ($this->stateChangeResult ?? true) && $success;
        }
    }

    public function transitions(User $actor, int $courseId, int $versionId = 0): array
    {
        $actor = $this->fresh($actor);
        $course = $this->row('courses', $courseId);
        $this->authorise($actor, $course);
        $workflow = $this->workflow();
        $assoc = $workflow->getAssociation($courseId);
        if ($versionId) {
            $version = $this->row('course_versions', $versionId);
            if ((int) $version->course_id !== $courseId) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
            if ($version->status === 'draft') { $assoc = null; }
        }
        $stageId = $assoc ? (int) $assoc->stage_id : (int) $workflow->getDefaultStageByCategory((int) $course->catid);
        $q = $this->db->createQuery()->select('t.id, t.title, t.options')->from('#__workflow_transitions AS t')
            ->innerJoin('#__workflow_stages AS s ON s.workflow_id = t.workflow_id')
            ->innerJoin('#__workflow_stages AS dest ON dest.id = t.to_stage_id AND dest.workflow_id = t.workflow_id')
            ->innerJoin('#__workflows AS w ON w.id = t.workflow_id')
            ->where('s.id = :stage')->where('(t.from_stage_id = s.id OR t.from_stage_id = -1)')
            ->where('t.published = 1 AND s.published = 1 AND dest.published = 1 AND w.published = 1')
            ->where('w.extension = :extension')->bind(':stage', $stageId, ParameterType::INTEGER);
        $extension = 'com_jlms.course';
        $q->bind(':extension', $extension)->order('t.ordering, t.id');
        return array_values(array_filter($this->db->setQuery($q)->loadObjectList(), static function ($transition) use ($actor): bool {
            $state = (new Registry($transition->options))->get('publishing');
            return in_array((string) $state, ['0', '1'], true)
                && $actor->authorise('core.execute.transition', 'com_jlms.transition.' . (int) $transition->id);
        }));
    }

    /** Read-only review of a publishing update; initial publication and withdrawal need no update review. */
    public function updateImpact(User $identity, int $courseId, int $versionId, int $transitionId): ?array
    {
        $actor = $this->fresh($identity);
        $course = $this->row('courses', $courseId); $this->authorise($actor, $course);
        $version = $this->row('course_versions', $versionId);
        if ((int)$version->course_id !== $courseId) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
        $selected = null;
        foreach ($this->transitions($actor, $courseId, $versionId) as $candidate) { if ((int)$candidate->id === $transitionId) { $selected = $candidate; } }
        if (!$selected) { throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED'); }
        if ($version->status !== 'draft' || $course->current_version_id === null || (int)(new Registry($selected->options))->get('publishing') !== 1) { return null; }
        if (($course->checked_out && (int)$course->checked_out !== (int)$actor->id) || ($version->checked_out && (int)$version->checked_out !== (int)$actor->id)) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
        $source = $this->row('course_versions', (int)$course->current_version_id);
        if ((int)$source->course_id !== $courseId || $source->status !== 'published' || (int)$source->id === $versionId) { throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE'); }
        return (new PublicationImpact($this->db))->build($course, $source, $version, $transitionId, (int)$actor->id);
    }

    private function validateAndFreeze(object $course, object $version, User $actor, array $remoteChecks = []): void
    {
        $versionId = (int) $version->id;
        // Types/features not yet served by the learner application cannot be published accidentally.
        foreach (['prerequisites'] as $table) {
            $q = $this->db->createQuery()->select('COUNT(*)')->from($this->db->quoteName('#__jlms_' . $table))
                ->where('course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER);
            if ((int) $this->db->setQuery($q)->loadResult()) {
                throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
            }
        }
        $orphan=$this->db->createQuery()->select('COUNT(*)')->from('#__jlms_quizzes AS q')
            ->leftJoin('#__jlms_lessons AS l ON l.id=q.lesson_id AND l.course_version_id=q.course_version_id')
            ->where('q.course_version_id = :version')->where("(l.id IS NULL OR l.kind <> 'quiz')")->bind(':version',$versionId,ParameterType::INTEGER);
        if((int)$this->db->setQuery($orphan)->loadResult())throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
        $q = $this->db->createQuery()->select('id, logical_key, title, ordering, params_json')->from('#__jlms_modules')
            ->where('course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER)->order('ordering, id');
        $modules = $this->db->setQuery($q, 0, 1001)->loadAssocList();
        if (!$modules || count($modules) > 1000) {
            throw new \DomainException('COM_JLMS_PUBLICATION_EMPTY');
        }
        $moduleIds = array_fill_keys(array_column($modules, 'id'), 0);
        // Keyset batches bound memory even on drivers which buffer complete query results.
        $lessons = [];
        $required = 0;
        $lastId = 0;
        do {
            $q = $this->db->createQuery()->select('*')->from('#__jlms_lessons')
                ->where('course_version_id = :version')->where('id > :after')
                ->bind(':version', $versionId, ParameterType::INTEGER)->bind(':after', $lastId, ParameterType::INTEGER)->order('id');
            $batch = $this->db->setQuery($q, 0, 16)->loadObjectList();
            foreach ($batch as $lesson) {
                if (count($lessons) >= 5000 || !array_key_exists($lesson->module_id, $moduleIds)) {
                    throw new \DomainException('COM_JLMS_INVALID_INPUT');
                }
                if (($lesson->media_asset_id !== null && $lesson->provider_key !== 'html5') || (int) $lesson->preview !== 0 || $lesson->publish_up !== null
                    || $lesson->publish_down !== null || $lesson->release_after_days !== null || strlen((string) $lesson->body) > 1048576) {
                    throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                }
                $lessonFields = $this->snapshotLessonFields($actor, (int) $lesson->id);
                $lesson->fields_snapshot_json = json_encode(['_schema' => 1, 'fields' => $lessonFields], JSON_THROW_ON_ERROR);
                $quizHash = null;
                if ($lesson->kind === 'quiz') {
                    $quizHash = $this->app->bootComponent('com_jlms')->getQuizSnapshotService()->freeze($actor, $course, $lesson);
                } elseif ($lesson->kind === 'text') {
                    if ($lesson->completion_rule !== 'manual' || $lesson->source_json !== null || $lesson->provider_key !== null
                        || trim(html_entity_decode(strip_tags((string) $lesson->body), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '') {
                        throw new \DomainException('COM_JLMS_PUBLICATION_EMPTY');
                    }
                } elseif ($lesson->kind === 'video') {
                    $policy = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked');
                    $params = json_decode($lesson->params_json, true, 16, JSON_THROW_ON_ERROR);
                    if (!is_string($lesson->provider_key) || !preg_match('/\A[a-z][a-z0-9_]{1,63}\z/D', $lesson->provider_key)
                        || $lesson->completion_rule !== 'video_coverage') {
                        throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                    }
                    if ($lesson->provider_key !== 'html5' && !in_array($policy, ['embed', 'site_consent'], true)) {
                        throw new \DomainException('COM_JLMS_VIDEO_EDITOR_POLICY_BLOCKED');
                    }
                    if ((int) $lesson->duration_ms < 1000 || (int) $lesson->duration_ms > 28800000
                        || ($params['video_duration_confirmed'] ?? false) !== true) {
                        throw new \DomainException('COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED');
                    }
                    if ($lesson->provider_key === 'html5') { $this->app->bootComponent('com_jlms')->getLocalVideoService()->assertReady($lesson); }
                    if ($lesson->provider_key === 'cloudflarestream') {
                        $proof = $remoteChecks[(int) $lesson->id] ?? null;
                        if (!$proof || !hash_equals($proof['hash'], hash('sha256', $lesson->source_json))
                            || $proof['source_revision'] !== (int) $lesson->source_revision
                            || !\Jlms\Component\Jlms\Administrator\Domain\VideoDuration::compatible((int)$lesson->duration_ms, (int)$proof['duration_ms'])) {
                            throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                        }
                    }
                    $provider = $this->app->bootComponent('com_jlms')->getCurriculumService()->videoProvider($lesson->provider_key);
                    $source = json_decode($lesson->source_json, true, 16, JSON_THROW_ON_ERROR);
                    $normal = $provider->normaliseSource(new \Jlms\Contracts\Video\SourceInput($source['reference'], $source['options'] ?? []));
                    if (!$provider->getDescriptor()->configured) {
                        throw new \DomainException('COM_JLMS_VIDEO_EDITOR_CONFIG_REQUIRED');
                    }
                    if (!$provider->getCapabilities($normal)->position) {
                        throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                    }
                } else { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
                $moduleIds[$lesson->module_id]++;
                $required += (int) $lesson->required;
                $lessons[] = ['id' => (int) $lesson->id, 'module_id' => (int) $lesson->module_id,
                    'logical_key' => $lesson->logical_key, 'title' => $lesson->title, 'ordering' => (int) $lesson->ordering,
                    'required' => (int) $lesson->required, 'kind' => $lesson->kind, 'quiz_sha256' => $quizHash, 'duration_ms' => $lesson->duration_ms,
                    'provider_key' => $lesson->provider_key, 'source_revision' => (int) $lesson->source_revision,
                    'fields_sha256' => hash('sha256', $lesson->fields_snapshot_json),
                    'source_sha256' => hash('sha256', (string) $lesson->source_json), 'body_sha256' => hash('sha256', (string) $lesson->body)];
                $lessonSnapshot = (object) ['id' => (int) $lesson->id, 'fields_snapshot_json' => $lesson->fields_snapshot_json];
                $this->db->updateObject('#__jlms_lessons', $lessonSnapshot, 'id');
                $lastId = (int) $lesson->id;
            }
        } while (count($batch) === 16);
        if (!$required || in_array(0, array_values($moduleIds), true)) {
            throw new \DomainException('COM_JLMS_PUBLICATION_EMPTY');
        }
        $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode($version->rules_json);
        if (($rules['mode'] ?? '') !== 'learning' || ($rules['sequence_mode'] ?? '') !== 'free'
            || ($rules['certificate_enabled'] ?? true) !== false || ($rules['completion_policy'] ?? '') !== 'all_required'
            || $version->fields_snapshot_json !== '{}') {
            throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
        }
        $q = $this->db->createQuery()->select('a.id, a.lesson_id, a.title, a.description, a.ordering, a.state, a.preview, a.publish_up, a.publish_down, f.id AS asset_id, f.sha256, f.byte_size, f.mime_type, f.status, f.scan_json')
            ->from('#__jlms_attachments AS a')->innerJoin('#__jlms_assets AS f ON f.id = a.asset_id')
            ->where('a.course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER)->order('a.ordering, a.id');
        $attachments = $this->db->setQuery($q, 0, 1001)->loadAssocList();
        if (count($attachments) > 1000) { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
        foreach ($attachments as $attachment) {
            $scan = json_decode($attachment['scan_json'] ?? '{}', true);
            $scanRequired = (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('material_scan_required', false);
            if (($scanRequired && (($scan['engine'] ?? '') !== 'clamd' || ($scan['result'] ?? '') !== 'clean'))
                || $attachment['status'] !== 'ready' || (int) $attachment['preview'] !== 0
                || !\Jlms\Component\Jlms\Administrator\Domain\MaterialAvailability::validStored($attachment['publish_up'], $attachment['publish_down'])) {
                throw new \DomainException('COM_JLMS_MATERIAL_NOT_READY');
            }
        }
        CoursePresentation::requireDescription($course);
        $presentation = TeacherProfiles::freeze($this->db, $actor, $course, CoursePresentation::snapshot($course));
        $hash = hash('sha256', json_encode(['_schema' => 1, 'title' => $course->title, 'rules' => $rules,
            'presentation' => $presentation, 'modules' => $modules, 'lessons' => $lessons, 'attachments' => $attachments], JSON_THROW_ON_ERROR));
        $now = gmdate('Y-m-d H:i:s');
        $update = (object) ['id' => $versionId, 'status' => 'published', 'title_snapshot' => $course->title,
            'presentation_json' => $presentation, 'structure_hash' => $hash, 'published_at' => $now,
            'revision' => (int) $version->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => $now];
        $this->db->updateObject('#__jlms_course_versions', $update, 'id');
        $calendar=CourseSchedule::read($course);
        $edition = (object) ['starts_at'=>$calendar['starts_at'],'ends_at'=>$calendar['ends_at'],'course_id' => (int) $course->id, 'course_version_id' => $versionId,
            'title' => $course->title, 'code' => 'default-v' . (int) $version->version_no, 'status' => 'open',
            'revision' => 1, 'created_by' => (int) $actor->id, 'modified_by' => (int) $actor->id,
            'created_at' => $now, 'modified_at' => $now];
        $this->db->insertObject('#__jlms_editions', $edition);
    }

    /** Freeze lesson field values and their display metadata into the immutable teaching version. */
    private function snapshotLessonFields(User $actor, int $lessonId): array
    {
        $context = 'com_jlms.lesson';
        $item = (string) $lessonId;
        $q = $this->db->createQuery()->select('f.id, f.name, f.title, f.type, f.access, v.value')
            ->from('#__fields AS f')->innerJoin('#__fields_values AS v ON v.field_id = f.id')
            ->where('f.context = :context')->where('f.state = 1')->where('v.item_id = :item')
            ->bind(':context', $context)->bind(':item', $item)->order('f.id');
        $fields = [];
        foreach ($this->db->setQuery($q, 0, 1001)->loadObjectList() as $row) {
            if (!in_array((int) $row->access, array_map('intval', $actor->getAuthorisedViewLevels()), true)) { continue; }
            $fields[$row->name] ??= ['label' => (string) $row->title, 'type' => (string) $row->type,
                'access' => (int) $row->access, 'values' => []];
            $fields[$row->name]['values'][] = (string) $row->value;
        }
        ksort($fields, SORT_STRING);
        return $fields;
    }

    public function transition(User $actor, int $courseId, int $versionId, int $courseRevision, int $versionRevision, int $transitionId, ?string $expectedImpact = null): void
    {
        $actor = $this->fresh($actor);
        $remoteChecks = [];
        $course = $this->row('courses', $courseId);
        $this->authorise($actor, $course);
        if (!\Joomla\CMS\Plugin\PluginHelper::isEnabled('workflow', 'publishing')) {
            throw new \DomainException('COM_JLMS_PUBLICATION_PLUGIN_REQUIRED');
        }
        $version = $this->row('course_versions', $versionId);
        if ((int) $version->course_id !== $courseId || (int) $version->revision !== $versionRevision
            || (int) $course->revision !== $courseRevision) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
        if ($version->status === 'draft' && $course->current_version_id !== null && $expectedImpact === null) { throw new \DomainException('COM_JLMS_PUBLICATION_REVIEW_REQUIRED'); }
        if ($version->status === 'draft') {
            $q = $this->db->createQuery()->select('id, source_json, source_revision')->from('#__jlms_lessons')
                ->where('course_version_id = :version')->where($this->db->quoteName('provider_key') . ' = ' . $this->db->quote('cloudflarestream'))
                ->bind(':version', $versionId, ParameterType::INTEGER)->order('id');
            $remoteLessons = $this->db->setQuery($q, 0, 101)->loadObjectList();
            if (count($remoteLessons) > 100) { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
            foreach ($remoteLessons as $lesson) {
                $source = json_decode($lesson->source_json, true, 16, JSON_THROW_ON_ERROR);
                $provider = $this->app->bootComponent('com_jlms')->getCurriculumService()->videoProvider('cloudflarestream');
                $metadata = $provider->fetchMetadata(new \Jlms\Contracts\Video\VideoSource('cloudflarestream', $source['reference'], (int) $lesson->source_revision),
                    new \Jlms\Contracts\Video\MetadataContext((int) $actor->id, $this->app->getLanguage()->getTag(), true));
                $remoteChecks[(int) $lesson->id] = ['hash' => hash('sha256', $lesson->source_json),
                    'source_revision' => (int) $lesson->source_revision, 'duration_ms' => $metadata->durationMs];
            }
        }
        $this->db->transactionStart();
        try {
            $actor = $this->fresh($actor);
            $course = $this->row('courses', $courseId, true);
            $this->authorise($actor, $course);
            $version = $this->row('course_versions', $versionId, true);
            if ((int) $version->course_id !== $courseId) {
                throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
            }
            if ((int) $course->revision !== $courseRevision || (int) $version->revision !== $versionRevision
                || ($course->checked_out && (int) $course->checked_out !== (int) $actor->id)
                || ($version->checked_out && (int) $version->checked_out !== (int) $actor->id)) {
                throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
            }
            $selected = null;
            foreach ($this->transitions($actor, $courseId, $versionId) as $candidate) {
                if ((int) $candidate->id === $transitionId) {
                    $selected = $candidate;
                }
            }
            if (!$selected) {
                throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED');
            }
            $state = (int) (new Registry($selected->options))->get('publishing');
            if ($version->status === 'draft' && $state === 1) {
                if ($course->current_version_id !== null) {
                    if ($expectedImpact === null) { throw new \DomainException('COM_JLMS_PUBLICATION_REVIEW_REQUIRED'); }
                    $impact = $this->updateImpact($actor, $courseId, $versionId, $transitionId);
                    if (!$impact || !hash_equals($impact['fingerprint'], $expectedImpact)) { throw new \DomainException('COM_JLMS_PUBLICATION_REVIEW_STALE'); }
                }
                $this->validateAndFreeze($course, $version, $actor, $remoteChecks);
            } elseif ($version->status !== 'published' || (int) $course->current_version_id !== $versionId) {
                throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE');
            }
            $workflow = $this->workflow();
            $association = $workflow->getAssociation($courseId);
            $publishingUpdate = $course->current_version_id !== null && (int) $course->current_version_id !== $versionId;
            if ($publishingUpdate && $association) {
                $stage = (int) $workflow->getDefaultStageByCategory((int) $course->catid);
                if (!$stage || !$workflow->updateAssociations([$courseId], $stage)) { throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED'); }
            }
            if (!$association) {
                $stage = (int) $workflow->getDefaultStageByCategory((int) $course->catid);
                if (!$stage || !$workflow->createAssociation($courseId, $stage)) {
                    throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED');
                }
            }
            $update = (object) ['id' => $courseId, 'current_version_id' => $versionId,
                'revision' => (int) $course->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => gmdate('Y-m-d H:i:s')];
            $this->db->updateObject('#__jlms_courses', $update, 'id');
            $this->permittedChange = [$courseId, $state];
            if (!$workflow->executeTransition([$courseId], $transitionId) || $this->stateChangeResult !== true
                || (int) $this->row('courses', $courseId)->state !== $state) {
                throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED');
            }
            $this->db->transactionCommit();
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        } finally {
            $this->permittedChange = null;
            $this->stateChangeResult = null;
        }
    }
}
