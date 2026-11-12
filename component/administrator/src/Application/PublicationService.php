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
        $version = null;
        if ($versionId) {
            $version = $this->row('course_versions', $versionId);
            if ((int) $version->course_id !== $courseId) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
        }
        $defaultStage = (int) $workflow->getDefaultStageByCategory((int) $course->catid);
        $stageIds = [$assoc ? (int) $assoc->stage_id : $defaultStage];
        $reviewStage = $this->stageId('COM_JLMS_IN_REVIEW');
        // A working version can use both the category's draft transition and the
        // course's current stage for review. Keep the legacy publish choice first.
        if ($version?->status === 'draft' && (!$assoc || (int) $assoc->stage_id !== $reviewStage)) {
            $stageIds = $assoc && (int) $assoc->stage_id !== $defaultStage
                ? [$defaultStage, (int) $assoc->stage_id]
                : [$defaultStage];
        }
        $stageIds = array_values(array_unique(array_filter($stageIds)));
        $extension = 'com_jlms.course';
        $transitions = [];
        foreach ($stageIds as $stageId) {
            $q = $this->db->createQuery()->select('t.id, t.title, t.options, t.from_stage_id')->from('#__workflow_transitions AS t')
                ->innerJoin('#__workflow_stages AS s ON s.workflow_id = t.workflow_id')
                ->innerJoin('#__workflow_stages AS dest ON dest.id = t.to_stage_id AND dest.workflow_id = t.workflow_id')
                ->innerJoin('#__workflows AS w ON w.id = t.workflow_id')
                ->where('s.id = :stage')->where('(t.from_stage_id = s.id OR t.from_stage_id = -1)')
                ->where('t.published = 1 AND s.published = 1 AND dest.published = 1 AND w.published = 1')
                ->where('w.extension = :extension')->bind(':stage', $stageId, ParameterType::INTEGER)
                ->bind(':extension', $extension)->order('t.ordering, t.id');
            foreach ($this->db->setQuery($q)->loadObjectList() as $transition) {
                $transition->jlms_source_stage_id = $stageId;
                $transitions[(int) $transition->id] = $transition;
            }
        }
        $currentState = (int) $course->state;
        $returnState = $course->current_version_id === null ? 0 : 1;
        $currentVersionId = (int) ($course->current_version_id ?? 0);
        return array_values(array_filter($transitions, function ($transition) use ($actor, $version, $currentState, $returnState, $reviewStage, $currentVersionId): bool {
            $options = new Registry($transition->options);
            $state = $options->get('publishing');
            $action = (string) $options->get('jlms_action', (string) $state === '1' ? 'publish' : 'unpublish');
            if (!in_array((string) $state, ['0', '1', '2'], true)) { return false; }
            if ($version?->status === 'draft') {
                if ($action === 'review' && (int) $state !== $currentState) { return false; }
                if ($action === 'return' && ((int) $transition->jlms_source_stage_id !== $reviewStage || (int) $state !== $returnState)) { return false; }
                if ($action === 'publish' && (int) $state !== 1) { return false; }
                if (!in_array($action, ['review', 'return', 'publish'], true)) { return false; }
            } elseif ($version && ($version->status !== 'published' || (int) $version->id !== $currentVersionId)) {
                return false;
            } elseif ($action === 'review' || $action === 'return') {
                return false;
            } elseif ($action === 'archive' && ($currentState !== 1 || (int) $state !== 2)) {
                return false;
            } elseif ($action === 'restore' && ($currentState !== 2 || (int) $state !== 1)) {
                return false;
            } elseif (!in_array($action, ['publish', 'unpublish', 'archive', 'restore'], true)) {
                return false;
            }
            return $actor->authorise('core.execute.transition', 'com_jlms.transition.' . (int) $transition->id);
        }));
    }

    private function stageId(string $title): int
    {
        $extension = 'com_jlms.course';
        $q = $this->db->createQuery()->select('s.id')->from('#__workflow_stages AS s')
            ->innerJoin('#__workflows AS w ON w.id = s.workflow_id')
            ->where('s.title = :title')->where('w.extension = :extension')
            ->bind(':title', $title)->bind(':extension', $extension);
        return (int) $this->db->setQuery($q)->loadResult();
    }

    public function isInReview(int $courseId): bool
    {
        $extension = 'com_jlms.course';
        $stageTitle = 'COM_JLMS_IN_REVIEW';
        $q = $this->db->createQuery()->select('COUNT(*)')->from('#__workflow_associations AS a')
            ->innerJoin('#__workflow_stages AS s ON s.id = a.stage_id')
            ->where('a.extension = :extension')->where('a.item_id = :course')->where('s.title = :stage')
            ->bind(':extension', $extension)->bind(':course', $courseId, ParameterType::INTEGER)
            ->bind(':stage', $stageTitle);
        return (int) $this->db->setQuery($q)->loadResult() > 0;
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
        $options = new Registry($selected->options);
        $action = (string) $options->get('jlms_action', (string) $options->get('publishing') === '1' ? 'publish' : 'unpublish');
        if ($version->status !== 'draft' || $course->current_version_id === null || $action !== 'publish') { return null; }
        if (($course->checked_out && (int)$course->checked_out !== (int)$actor->id) || ($version->checked_out && (int)$version->checked_out !== (int)$actor->id)) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
        $source = $this->row('course_versions', (int)$course->current_version_id);
        if ((int)$source->course_id !== $courseId || $source->status !== 'published' || (int)$source->id === $versionId) { throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE'); }
        return (new PublicationImpact($this->db))->build($course, $source, $version, $transitionId, (int)$actor->id);
    }

    private function validateAndFreeze(object $course, object $version, User $actor, array $remoteChecks = []): void
    {
        $versionId = (int) $version->id;
        $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode($version->rules_json);
        // Prerequisite edges are a supported learner rule; validate the graph before freezing it.
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
        $initialRequired = 0;
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
                if (($lesson->media_asset_id !== null && $lesson->provider_key !== 'html5') || (int) $lesson->preview !== 0
                    || strlen((string) $lesson->body) > 1048576) {
                    throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                }
                if (!\Jlms\Component\Jlms\Administrator\Domain\LessonAvailability::validStored($lesson->publish_up, $lesson->publish_down)
                    || !\Jlms\Component\Jlms\Administrator\Domain\LessonAvailability::validReleaseDays($lesson->release_after_days)) {
                    throw new \DomainException('COM_JLMS_LESSON_SCHEDULE_INVALID');
                }
                if ((int) $lesson->required === 1 && !\Jlms\Component\Jlms\Administrator\Domain\LessonAvailability::reachable(
                    $lesson->publish_up, $lesson->publish_down, CourseSchedule::read($course),
                )) {
                    throw new \DomainException('COM_JLMS_LESSON_UNREACHABLE');
                }
                $lessonFields = $this->snapshotFields($actor, 'com_jlms.lesson', (int) $lesson->id);
                $lesson->fields_snapshot_json = json_encode(['_schema' => 1, 'fields' => $lessonFields], JSON_THROW_ON_ERROR);
                $quizHash = null;
                if ($lesson->kind === 'quiz') {
                    if ($rules['mode'] === 'consultation') { throw new \DomainException('COM_JLMS_CONSULTATION_QUIZ_UNSUPPORTED'); }
                    $quizHash = $this->app->bootComponent('com_jlms')->getQuizSnapshotService()->freeze($actor, $course, $lesson);
                } elseif ($lesson->kind === 'text') {
                    if ($lesson->completion_rule !== 'manual' || $lesson->source_json !== null || $lesson->provider_key !== null
                        || trim(html_entity_decode(strip_tags((string) $lesson->body), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '') {
                        throw new \DomainException('COM_JLMS_PUBLICATION_EMPTY');
                    }
                } elseif ($lesson->kind === 'document') {
                    if ($lesson->completion_rule !== 'manual' || $lesson->provider_key !== null || $lesson->source_json !== null
                        || $lesson->media_asset_id !== null || $lesson->duration_ms !== null) {
                        throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                    }
                    $materialQuery = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_attachments AS a')
                        ->innerJoin('#__jlms_assets AS f ON f.id = a.asset_id')
                        ->where('a.course_version_id = :version')->where('a.lesson_id = :lesson')
                        ->where('a.state = 1')->where('a.preview = 0')->where("f.status = 'ready'")
                        ->bind(':version', (int) $version->id, ParameterType::INTEGER)
                        ->bind(':lesson', (int) $lesson->id, ParameterType::INTEGER);
                    if ((int) $this->db->setQuery($materialQuery)->loadResult() < 1) {
                        throw new \DomainException('COM_JLMS_PUBLICATION_EMPTY');
                    }
                } elseif ($lesson->kind === 'audio') {
                    if ($lesson->completion_rule !== 'manual' || $lesson->provider_key !== null || $lesson->source_json !== null
                        || $lesson->media_asset_id !== null || $lesson->duration_ms !== null) { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
                    $materialQuery = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_attachments AS a')
                        ->innerJoin('#__jlms_assets AS f ON f.id = a.asset_id')->where('a.course_version_id = :version')
                        ->where('a.lesson_id = :lesson')->where('a.state = 1')->where('a.preview = 0')
                        ->where("f.status = 'ready'")->where("f.mime_type IN ('audio/mpeg', 'audio/ogg')")
                        ->bind(':version', (int) $version->id, ParameterType::INTEGER)->bind(':lesson', (int) $lesson->id, ParameterType::INTEGER);
                    if ((int) $this->db->setQuery($materialQuery)->loadResult() < 1) { throw new \DomainException('COM_JLMS_AUDIO_MATERIAL_REQUIRED'); }
                } elseif ($lesson->kind === 'video') {
                    $policy = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked');
                    $params = json_decode($lesson->params_json, true, 16, JSON_THROW_ON_ERROR);
                    if (!is_array($params) || (array_key_exists('video_threshold_basis_points', $params)
                        && (!is_int($params['video_threshold_basis_points']) || $params['video_threshold_basis_points'] < 1 || $params['video_threshold_basis_points'] > 10000))) {
                        throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                    }
                    if (!is_string($lesson->provider_key) || !preg_match('/\A[a-z][a-z0-9_]{1,63}\z/D', $lesson->provider_key)
                        || $lesson->completion_rule !== 'video_coverage') {
                        throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                    }
                    if ($lesson->provider_key !== 'html5' && !in_array($policy, ['embed', 'site_consent'], true)) {
                        throw new \DomainException('COM_JLMS_VIDEO_EDITOR_POLICY_BLOCKED');
                    }
                    if ($rules['mode'] !== 'consultation' && ((int) $lesson->duration_ms < 1000 || (int) $lesson->duration_ms > 28800000
                        || ($params['video_duration_confirmed'] ?? false) !== true)) {
                        throw new \DomainException('COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED');
                    }
                    if ($lesson->provider_key === 'html5') { $this->app->bootComponent('com_jlms')->getLocalVideoService()->assertReady($lesson); }
                    if ($lesson->provider_key === 'cloudflarestream') {
                        $proof = $remoteChecks[(int) $lesson->id] ?? null;
                        if ($rules['mode'] !== 'consultation' && (!$proof || !hash_equals($proof['hash'], hash('sha256', $lesson->source_json))
                            || $proof['source_revision'] !== (int) $lesson->source_revision
                            || !\Jlms\Component\Jlms\Administrator\Domain\VideoDuration::compatible((int)$lesson->duration_ms, (int)$proof['duration_ms']))) {
                            throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                        }
                    }
                    $provider = $this->app->bootComponent('com_jlms')->getCurriculumService()->videoProvider($lesson->provider_key);
                    $source = json_decode($lesson->source_json, true, 16, JSON_THROW_ON_ERROR);
                    $normal = $provider->normaliseSource(new \Jlms\Contracts\Video\SourceInput($source['reference'], $source['options'] ?? []));
                    if (!$provider->getDescriptor()->configured) {
                        throw new \DomainException('COM_JLMS_VIDEO_EDITOR_CONFIG_REQUIRED');
                    }
                    if ($rules['mode'] !== 'consultation' && !$provider->getCapabilities($normal)->position) {
                        throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
                    }
                } else { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
                $moduleIds[$lesson->module_id]++;
                $required += (int) $lesson->required;
                if ((int) $lesson->required === 1 && (int) ($lesson->release_after_days ?? 0) === 0) { $initialRequired++; }
                $lessons[] = ['id' => (int) $lesson->id, 'module_id' => (int) $lesson->module_id,
                    'logical_key' => $lesson->logical_key, 'title' => $lesson->title, 'ordering' => (int) $lesson->ordering,
                    'required' => (int) $lesson->required, 'kind' => $lesson->kind, 'quiz_sha256' => $quizHash, 'duration_ms' => $lesson->duration_ms,
                    'publish_up' => $lesson->publish_up, 'publish_down' => $lesson->publish_down,
                    'release_after_days' => $lesson->release_after_days === null ? null : (int) $lesson->release_after_days,
                    'provider_key' => $lesson->provider_key, 'source_revision' => (int) $lesson->source_revision,
                    'fields_sha256' => hash('sha256', $lesson->fields_snapshot_json),
                    'source_sha256' => hash('sha256', (string) $lesson->source_json), 'body_sha256' => hash('sha256', (string) $lesson->body)];
                $lessonSnapshot = (object) ['id' => (int) $lesson->id, 'fields_snapshot_json' => $lesson->fields_snapshot_json];
                $this->db->updateObject('#__jlms_lessons', $lessonSnapshot, 'id');
                $lastId = (int) $lesson->id;
            }
        } while (count($batch) === 16);
        if (in_array(0, array_values($moduleIds), true) || (!$required && $rules['mode'] !== 'consultation')) {
            throw new \DomainException('COM_JLMS_PUBLICATION_EMPTY');
        }
        if ($rules['mode'] === 'consultation' && $required > 0) {
            throw new \DomainException('COM_JLMS_CONSULTATION_COURSE_HAS_REQUIRED_LESSONS');
        }
        if ($rules['mode'] === 'learning' && $initialRequired === 0) {
            throw new \DomainException('COM_JLMS_REQUIRED_INITIAL_LESSON_REQUIRED');
        }
        $prerequisiteMap = [];
        $lessonIds = array_fill_keys(array_map(static fn(array $row): int => (int) $row['id'], $lessons), true);
        $edgeQuery = $this->db->createQuery()->select('lesson_id, required_lesson_id')->from('#__jlms_prerequisites')
            ->where('course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER)->order('lesson_id, required_lesson_id');
        $edges = $this->db->setQuery($edgeQuery, 0, 25001)->loadObjectList();
        if (count($edges) > 25000) { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
        if ($rules['mode'] === 'consultation' && $edges) { throw new \DomainException('COM_JLMS_CONSULTATION_PREREQUISITES_UNSUPPORTED'); }
        foreach ($edges as $edge) {
            if (!isset($lessonIds[(int) $edge->lesson_id]) || !isset($lessonIds[(int) $edge->required_lesson_id])
                || (int) $edge->lesson_id === (int) $edge->required_lesson_id) { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
            $prerequisiteMap[(int) $edge->lesson_id][] = (int) $edge->required_lesson_id;
        }
        if ($rules['sequence_mode'] === 'sequential') {
            $sequenceQuery = $this->db->createQuery()->select('l.id, l.required')
                ->from('#__jlms_lessons AS l')->innerJoin('#__jlms_modules AS m ON m.id = l.module_id AND m.course_version_id = l.course_version_id')
                ->where('l.course_version_id = :sequenceVersion')->bind(':sequenceVersion', $versionId, ParameterType::INTEGER)
                ->order('m.ordering, m.id, l.ordering, l.id');
            $previousRequired = 0;
            foreach ($this->db->setQuery($sequenceQuery, 0, 5001)->loadObjectList() as $lesson) {
                $lessonId = (int) $lesson->id;
                if ($previousRequired && !in_array($previousRequired, $prerequisiteMap[$lessonId] ?? [], true)) {
                    $prerequisiteMap[$lessonId][] = $previousRequired;
                }
                if ((int) $lesson->required === 1) { $previousRequired = $lessonId; }
            }
        }
        $graphState = [];
        $visitPrerequisite = function (int $node) use (&$visitPrerequisite, &$graphState, $prerequisiteMap): void {
            if (($graphState[$node] ?? 0) === 1) { throw new \DomainException('COM_JLMS_PREREQUISITE_CYCLE'); }
            if (($graphState[$node] ?? 0) === 2) { return; }
            $graphState[$node] = 1;
            foreach ($prerequisiteMap[$node] ?? [] as $parent) { $visitPrerequisite((int) $parent); }
            $graphState[$node] = 2;
        };
        foreach (array_keys($lessonIds) as $node) { $visitPrerequisite((int) $node); }
        $q = $this->db->createQuery()->select('a.id, a.lesson_id, a.title, a.description, a.ordering, a.state, a.preview, a.publish_up, a.publish_down, f.id AS asset_id, f.sha256, f.byte_size, f.mime_type, f.status, f.scan_json')
            ->from('#__jlms_attachments AS a')->innerJoin('#__jlms_assets AS f ON f.id = a.asset_id')
            ->where('a.course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER)->order('a.ordering, a.id');
        $attachments = $this->db->setQuery($q, 0, 1001)->loadAssocList();
        if (count($attachments) > 1000) { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
        foreach ($attachments as $attachment) {
            $scan = json_decode($attachment['scan_json'] ?? '{}', true);
            $scanRequired = (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('material_scan_required', false);
            $preview = (int) $attachment['preview'];
            if (($scanRequired && (($scan['engine'] ?? '') !== 'clamd' || ($scan['result'] ?? '') !== 'clean'))
                || $attachment['status'] !== 'ready' || !in_array($preview, [0, 1], true)
                || ($preview === 1 && $attachment['lesson_id'] !== null)
                || !\Jlms\Component\Jlms\Administrator\Domain\MaterialAvailability::validStored($attachment['publish_up'], $attachment['publish_down'])) {
                throw new \DomainException('COM_JLMS_MATERIAL_NOT_READY');
            }
        }
        CoursePresentation::requireDescription($course);
        $presentation = TeacherProfiles::freeze($this->db, $actor, $course, CoursePresentation::snapshot($course));
        $courseFields = $this->snapshotFields($actor, 'com_jlms.course', (int) $course->id);
        $courseFieldsSnapshot = json_encode(['_schema' => 1, 'fields' => $courseFields], JSON_THROW_ON_ERROR);
        $hash = hash('sha256', json_encode(['_schema' => 1, 'title' => $course->title, 'rules' => $rules,
            'presentation' => $presentation, 'course_fields_sha256' => hash('sha256', $courseFieldsSnapshot),
            'modules' => $modules, 'lessons' => $lessons,
            'prerequisites' => $prerequisiteMap, 'attachments' => $attachments], JSON_THROW_ON_ERROR));
        $now = gmdate('Y-m-d H:i:s');
        $update = (object) ['id' => $versionId, 'status' => 'published', 'title_snapshot' => $course->title,
            'presentation_json' => $presentation, 'fields_snapshot_json' => $courseFieldsSnapshot,
            'structure_hash' => $hash, 'published_at' => $now,
            'revision' => (int) $version->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => $now];
        $this->db->updateObject('#__jlms_course_versions', $update, 'id');
        $calendar=CourseSchedule::read($course);
        $edition = (object) ['starts_at'=>$calendar['starts_at'],'ends_at'=>$calendar['ends_at'],'course_id' => (int) $course->id, 'course_version_id' => $versionId,
            'title' => $course->title, 'code' => 'default-v' . (int) $version->version_no, 'status' => 'open',
            'revision' => 1, 'created_by' => (int) $actor->id, 'modified_by' => (int) $actor->id,
            'created_at' => $now, 'modified_at' => $now];
        $this->db->insertObject('#__jlms_editions', $edition);
    }

    /** Freeze native field values and display metadata into the immutable teaching version. */
    private function snapshotFields(User $actor, string $context, int $itemId): array
    {
        if (!in_array($context, ['com_jlms.course', 'com_jlms.lesson'], true) || $itemId < 1) {
            throw new \DomainException('COM_JLMS_INVALID_INPUT');
        }
        $item = (string) $itemId;
        $q = $this->db->createQuery()->select('f.id, f.name, f.title, f.type, f.access, v.value')
            ->from('#__fields AS f')->innerJoin('#__fields_values AS v ON v.field_id = f.id')
            ->where('f.context = :context')->where('f.state = 1')->where('v.item_id = :item')
            ->bind(':context', $context)->bind(':item', $item)->order('f.id');
        $fields = [];
        $rows = $this->db->setQuery($q, 0, 1001)->loadObjectList();
        if (count($rows) > 1000) { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
        foreach ($rows as $row) {
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
        if ($version->status === 'draft' && $course->current_version_id !== null && $expectedImpact === null) {
            // Only actual publication requires impact confirmation. Sending a
            // version to review does not affect the live course or learners.
            $selected = null;
            foreach ($this->transitions($actor, $courseId, $versionId) as $candidate) { if ((int) $candidate->id === $transitionId) { $selected = $candidate; } }
            $action = $selected ? (string) (new Registry($selected->options))->get('jlms_action', '') : '';
            if ($action !== 'review' && $action !== 'return') { throw new \DomainException('COM_JLMS_PUBLICATION_REVIEW_REQUIRED'); }
        }
        $preselected = null;
        foreach ($this->transitions($actor, $courseId, $versionId) as $candidate) { if ((int) $candidate->id === $transitionId) { $preselected = $candidate; } }
        if (!$preselected) { throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED'); }
        $preOptions = new Registry($preselected->options);
        $preAction = (string) $preOptions->get('jlms_action', (string) $preOptions->get('publishing') === '1' ? 'publish' : 'unpublish');
        if ($version->status === 'draft' && $preAction === 'publish') {
            $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode($version->rules_json);
            $q = $this->db->createQuery()->select('id, source_json, source_revision')->from('#__jlms_lessons')
                ->where('course_version_id = :version')->where($this->db->quoteName('provider_key') . ' = ' . $this->db->quote('cloudflarestream'))
                ->bind(':version', $versionId, ParameterType::INTEGER)->order('id');
            $remoteLessons = $rules['mode'] === 'consultation' ? [] : $this->db->setQuery($q, 0, 101)->loadObjectList();
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
            $options = new Registry($selected->options);
            $state = (int) $options->get('publishing');
            $action = (string) $options->get('jlms_action', $state === 1 ? 'publish' : 'unpublish');
            if ($version->status === 'draft' && $action === 'review') {
                if ($state !== (int) $course->state || $expectedImpact !== null) { throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED'); }
            } elseif ($version->status === 'draft' && $action === 'return') {
                $returnState = $course->current_version_id === null ? 0 : 1;
                if ($state !== $returnState || $expectedImpact !== null) { throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED'); }
            } elseif ($version->status === 'draft' && $action === 'publish' && $state === 1) {
                if ($course->current_version_id !== null) {
                    if ($expectedImpact === null) { throw new \DomainException('COM_JLMS_PUBLICATION_REVIEW_REQUIRED'); }
                    $impact = $this->updateImpact($actor, $courseId, $versionId, $transitionId);
                    if (!$impact || !hash_equals($impact['fingerprint'], $expectedImpact)) { throw new \DomainException('COM_JLMS_PUBLICATION_REVIEW_STALE'); }
                }
                $this->validateAndFreeze($course, $version, $actor, $remoteChecks);
            } elseif ($action === 'publish' && $version->status === 'published'
                && (int) $course->current_version_id === $versionId && $state === 1) {
                // Republish the current immutable version without rebuilding its snapshot.
            } elseif ($action === 'unpublish' && $version->status === 'published' && (int) $course->current_version_id === $versionId && $state === 0) {
                // The current teaching version stays immutable; workflow withdrawal
                // only changes whether a new learner can find or start the course.
            } elseif ($action === 'archive' && $version->status === 'published'
                && (int) $course->current_version_id === $versionId && (int) $course->state === 1 && $state === 2) {
                // Archiving hides the course from discovery while preserving the live version and enrollments.
            } elseif ($action === 'restore' && $version->status === 'published'
                && (int) $course->current_version_id === $versionId && (int) $course->state === 2 && $state === 1) {
                // Restore the same immutable version and its learner history.
            } else {
                throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE');
            }
            $workflow = $this->workflow();
            $association = $workflow->getAssociation($courseId);
            $sourceStage = (int) $selected->jlms_source_stage_id;
            if (!$association) {
                if (!$sourceStage || !$workflow->createAssociation($courseId, $sourceStage)) {
                    throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED');
                }
            } elseif ((int) $association->stage_id !== $sourceStage
                && !$workflow->updateAssociations([$courseId], $sourceStage)) {
                throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED');
            }
            $update = (object) ['id' => $courseId,
                'revision' => (int) $course->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => gmdate('Y-m-d H:i:s')];
            if ($version->status === 'draft' && $action === 'publish') { $update->current_version_id = $versionId; }
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
