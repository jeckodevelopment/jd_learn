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

    public function transitions(User $actor, int $courseId): array
    {
        $course = $this->row('courses', $courseId);
        $this->authorise($actor, $course);
        $workflow = $this->workflow();
        $assoc = $workflow->getAssociation($courseId);
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

    private function validateAndFreeze(object $course, object $version, User $actor): void
    {
        $versionId = (int) $version->id;
        // Types/features not yet served by the learner application cannot be published accidentally.
        foreach (['prerequisites', 'attachments', 'quizzes'] as $table) {
            $q = $this->db->createQuery()->select('COUNT(*)')->from($this->db->quoteName('#__jlms_' . $table))
                ->where('course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER);
            if ((int) $this->db->setQuery($q)->loadResult()) {
                throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
            }
        }
        $q = $this->db->createQuery()->select('id, logical_key, title, ordering, params_json')->from('#__jlms_modules')
            ->where('course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER)->order('ordering, id');
        $modules = $this->db->setQuery($q, 0, 1001)->loadAssocList();
        if (!$modules || count($modules) > 1000) {
            throw new \DomainException('COM_JLMS_PUBLICATION_EMPTY');
        }
        $moduleIds = array_fill_keys(array_column($modules, 'id'), 0);
        $q = $this->db->createQuery()->select('*')->from('#__jlms_lessons')
            ->where('course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER)->order('module_id, ordering, id');
        // Hash bodies one at a time: never accumulate up to 5,000 MEDIUMTEXT values in memory.
        $iterator = $this->db->setQuery($q)->getIterator();
        $lessons = [];
        $required = 0;
        foreach ($iterator as $lesson) {
            if (count($lessons) >= 5000 || !array_key_exists($lesson->module_id, $moduleIds)) {
                throw new \DomainException('COM_JLMS_INVALID_INPUT');
            }
            if ($lesson->kind !== 'text' || $lesson->completion_rule !== 'manual' || $lesson->source_json !== null
                || $lesson->provider_key !== null || $lesson->media_asset_id !== null || (int) $lesson->preview !== 0
                || $lesson->publish_up !== null || $lesson->publish_down !== null || $lesson->release_after_days !== null) {
                throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
            }
            if (trim(html_entity_decode(strip_tags((string) $lesson->body), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '') {
                throw new \DomainException('COM_JLMS_PUBLICATION_EMPTY');
            }
            $moduleIds[$lesson->module_id]++;
            $required += (int) $lesson->required;
            $lessons[] = ['id' => (int) $lesson->id, 'module_id' => (int) $lesson->module_id,
                'logical_key' => $lesson->logical_key, 'title' => $lesson->title, 'ordering' => (int) $lesson->ordering,
                'required' => (int) $lesson->required, 'body_sha256' => hash('sha256', (string) $lesson->body)];
        }
        if (!$required || in_array(0, array_values($moduleIds), true)) {
            throw new \DomainException('COM_JLMS_PUBLICATION_EMPTY');
        }
        $rules = json_decode($version->rules_json, true, 32, JSON_THROW_ON_ERROR);
        if (($rules['mode'] ?? '') !== 'learning' || ($rules['sequence_mode'] ?? '') !== 'free'
            || ($rules['certificate_enabled'] ?? true) !== false || ($rules['completion_policy'] ?? '') !== 'all_required'
            || $version->fields_snapshot_json !== '{}') {
            throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
        }
        $presentation = json_encode(['_schema' => 1, 'summary' => $course->summary], JSON_THROW_ON_ERROR);
        $hash = hash('sha256', json_encode(['_schema' => 1, 'title' => $course->title, 'rules' => $rules,
            'presentation' => $presentation, 'modules' => $modules, 'lessons' => $lessons], JSON_THROW_ON_ERROR));
        $now = gmdate('Y-m-d H:i:s');
        $update = (object) ['id' => $versionId, 'status' => 'published', 'title_snapshot' => $course->title,
            'presentation_json' => $presentation, 'structure_hash' => $hash, 'published_at' => $now,
            'revision' => (int) $version->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => $now];
        $this->db->updateObject('#__jlms_course_versions', $update, 'id');
        $edition = (object) ['course_id' => (int) $course->id, 'course_version_id' => $versionId,
            'title' => $course->title, 'code' => 'default-v' . (int) $version->version_no, 'status' => 'open',
            'revision' => 1, 'created_by' => (int) $actor->id, 'modified_by' => (int) $actor->id,
            'created_at' => $now, 'modified_at' => $now];
        $this->db->insertObject('#__jlms_editions', $edition);
    }

    public function transition(User $actor, int $courseId, int $versionId, int $courseRevision, int $versionRevision, int $transitionId): void
    {
        $this->db->transactionStart();
        try {
            $course = $this->row('courses', $courseId, true);
            $this->authorise($actor, $course);
            $version = $this->row('course_versions', $versionId, true);
            if ((int) $version->course_id !== $courseId) {
                throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
            }
            if ((int) $course->revision !== $courseRevision || (int) $version->revision !== $versionRevision
                || ($course->checked_out && (int) $course->checked_out !== (int) $actor->id)) {
                throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
            }
            $selected = null;
            foreach ($this->transitions($actor, $courseId) as $candidate) {
                if ((int) $candidate->id === $transitionId) {
                    $selected = $candidate;
                }
            }
            if (!$selected) {
                throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED');
            }
            $state = (int) (new Registry($selected->options))->get('publishing');
            if ($version->status === 'draft' && $state === 1 && $course->current_version_id === null) {
                $this->validateAndFreeze($course, $version, $actor);
            } elseif ($version->status !== 'published' || (int) $course->current_version_id !== $versionId) {
                throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE');
            }
            $workflow = $this->workflow();
            if (!$workflow->getAssociation($courseId)) {
                $stage = (int) $workflow->getDefaultStageByCategory((int) $course->catid);
                if (!$stage || !$workflow->createAssociation($courseId, $stage)) {
                    throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED');
                }
            }
            $update = (object) ['id' => $courseId, 'current_version_id' => $versionId,
                'revision' => (int) $course->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => gmdate('Y-m-d H:i:s')];
            $this->db->updateObject('#__jlms_courses', $update, 'id');
            $this->permittedChange = [$courseId, $state];
            if (!$workflow->executeTransition([$courseId], $transitionId) || (int) $this->row('courses', $courseId)->state !== $state) {
                throw new \DomainException('COM_JLMS_WORKFLOW_REJECTED');
            }
            $this->db->transactionCommit();
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        } finally {
            $this->permittedChange = null;
        }
    }
}
