<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\DecimalId;
use Jlms\Contracts\Video\SourceInput;
use Jlms\Contracts\Video\VideoProviderRegistryInterface;

/** Authoring transactions lock course, then teaching version. Published versions never change here. */
final class CurriculumService
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly \Closure $filterHtml,
        private readonly \Closure $registryFactory,
    ) {
    }

    private function row(string $table, int $id, bool $lock = false): object
    {
        $query = $this->db->createQuery()->select('*')->from($this->db->quoteName('#__jlms_' . $table))
            ->where($this->db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
        if ($lock) {
            $query->setQuery((string) $query . ' FOR UPDATE');
        }
        $row = $this->db->setQuery($query)->loadObject();
        if (!$row) {
            throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
        }
        return $row;
    }

    private function authorise(User $actor, object $course, string $action = 'core.edit'): void
    {
        $asset = 'com_jlms.course.' . (int) $course->id;
        $editOwn = $action === 'core.edit' && (int) $course->created_by === (int) $actor->id
            && $actor->authorise('core.edit.own', $asset);
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
            || (!$actor->authorise($action, $asset) && !$editOwn)) {
            throw new \DomainException('JERROR_ALERTNOAUTHOR');
        }
    }

    private function draft(User $actor, int $courseId, int $versionId, int $revision, string $action = 'core.edit'): object
    {
        $course = $this->row('courses', $courseId, true);
        $this->authorise($actor, $course, $action);
        $version = $this->row('course_versions', $versionId, true);
        if ((int) $version->course_id !== $courseId) {
            throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
        }
        if ($version->status !== 'draft') {
            throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE');
        }
        if ((int) $version->revision !== $revision) {
            throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
        }
        return $version;
    }

    private function transaction(\Closure $work): mixed
    {
        $this->db->transactionStart();
        try {
            $result = $work();
            $this->db->transactionCommit();
            return $result;
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }
    }

    public static function id(mixed $input, bool $allowZero = false): int
    {
        if (!is_int($input) && !is_string($input)) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
        }
        if ($allowZero && (string) $input === '0') {
            return 0;
        }
        return DecimalId::parse((string) $input);
    }

    private static function title(mixed $value): string
    {
        if (!is_string($value) || preg_match('/\A[^\r\n]{1,255}\z/u', trim($value)) !== 1) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_COURSE');
        }
        return trim($value);
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 15) | 64);
        $bytes[8] = chr((ord($bytes[8]) & 63) | 128);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
            . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }

    private function insert(string $table, array $fields, User $actor): int
    {
        $now = gmdate('Y-m-d H:i:s');
        $record = (object) ($fields + ['revision' => 1, 'created_by' => (int) $actor->id,
            'created_at' => $now, 'modified_by' => (int) $actor->id, 'modified_at' => $now]);
        $this->db->insertObject('#__jlms_' . $table, $record, 'id');
        return (int) $record->id;
    }

    private function update(string $table, object $record, array $fields, User $actor): void
    {
        $update = (object) (['id' => (int) $record->id] + $fields + [
            'revision' => (int) $record->revision + 1, 'modified_by' => (int) $actor->id,
            'modified_at' => gmdate('Y-m-d H:i:s')]);
        $this->db->updateObject('#__jlms_' . $table, $update, 'id', true);
    }

    private function listRows(string $table, string $field, int $id, int $limit = 1001): array
    {
        $columns = $table === 'modules'
            ? ['id', 'course_version_id', 'title', 'ordering', 'revision']
            : ['id', 'course_version_id', 'module_id', 'title', 'kind', 'required', 'ordering', 'revision'];
        $q = $this->db->createQuery()->select($this->db->quoteName($columns))->from($this->db->quoteName('#__jlms_' . $table))
            ->where($this->db->quoteName($field) . ' = :owner')->bind(':owner', $id, ParameterType::INTEGER)
            ->order($this->db->quoteName('ordering') . ', ' . $this->db->quoteName('id'));
        $rows = $this->db->setQuery($q, 0, $limit)->loadObjectList();
        if (count($rows) === $limit) {
            throw new \DomainException('COM_JLMS_OUTLINE_LIMIT');
        }
        return $rows;
    }

    public function outline(User $actor, int $courseId, int $versionId = 0): array
    {
        $course = $this->row('courses', $courseId);
        $this->authorise($actor, $course);
        $q = $this->db->createQuery()->select('id, course_id, version_no, status, revision')
            ->from($this->db->quoteName('#__jlms_course_versions'))->where('course_id = :course')
            ->bind(':course', $courseId, ParameterType::INTEGER)->order('version_no DESC');
        $versions = $this->db->setQuery($q, 0, 100)->loadObjectList();
        if (!$versionId && $versions) {
            foreach ($versions as $candidate) {
                if ($candidate->status === 'draft') {
                    $versionId = (int) $candidate->id;
                    break;
                }
            }
            $versionId = $versionId ?: (int) $versions[0]->id;
        }
        $version = null;
        foreach ($versions as $candidate) {
            if ((int) $candidate->id === $versionId) {
                $version = $candidate;
            }
        }
        if ($versionId && !$version) {
            throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
        }
        return ['course' => $course, 'versions' => $versions, 'version' => $version,
            'modules' => $version ? $this->listRows('modules', 'course_version_id', $versionId) : [],
            'lessons' => $version ? $this->listRows('lessons', 'course_version_id', $versionId, 5001) : []];
    }

    public function lessonForEdit(User $actor, int $courseId, int $versionId, int $lessonId): object
    {
        $course = $this->row('courses', $courseId);
        $this->authorise($actor, $course);
        $version = $this->row('course_versions', $versionId);
        $lesson = $this->row('lessons', $lessonId);
        if ((int) $version->course_id !== $courseId || (int) $lesson->course_version_id !== $versionId) {
            throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
        }
        return $lesson;
    }

    public function createDraft(User $actor, int $courseId, int $expectedRevision): int
    {
        return $this->transaction(function () use ($actor, $courseId, $expectedRevision): int {
            $course = $this->row('courses', $courseId, true);
            $this->authorise($actor, $course);
            $q = $this->db->createQuery()->select('id')->from($this->db->quoteName('#__jlms_course_versions'))
                ->where('course_id = :course')->where('status = :status');
            $status = 'draft';
            $q->bind(':course', $courseId, ParameterType::INTEGER)->bind(':status', $status);
            $existing = (int) $this->db->setQuery($q)->loadResult();
            if ($existing) {
                return $existing;
            }
            if ((int) $course->revision !== $expectedRevision) {
                throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
            }
            // Full historical cloning is deliberately gated until its quiz/resource/equivalence mapping is implemented.
            if ($course->current_version_id !== null) {
                throw new \DomainException('COM_JLMS_VERSION_COPY_NOT_AVAILABLE');
            }
            $q = $this->db->createQuery()->select('COALESCE(MAX(version_no), 0)')
                ->from($this->db->quoteName('#__jlms_course_versions'))->where('course_id = :course')
                ->bind(':course', $courseId, ParameterType::INTEGER);
            $number = (int) $this->db->setQuery($q)->loadResult() + 1;
            if ($number > 1) {
                throw new \DomainException('COM_JLMS_VERSION_COPY_NOT_AVAILABLE');
            }
            $id = $this->insert('course_versions', ['course_id' => $courseId, 'version_no' => $number,
                'status' => 'draft', 'title_snapshot' => $course->title,
                'rules_json' => '{"_schema":1,"mode":"learning","sequence_mode":"free","video_threshold_basis_points":9000,"certificate_enabled":false,"completion_policy":"all_required"}',
                'presentation_json' => json_encode(['_schema' => 1, 'summary' => $course->summary], JSON_THROW_ON_ERROR),
                'fields_snapshot_json' => '{}'], $actor);
            $this->update('courses', $course, [], $actor);
            return $id;
        });
    }

    public function saveModule(User $actor, array $input): int
    {
        $courseId = self::id($input['course_id'] ?? null);
        $versionId = self::id($input['version_id'] ?? null);
        $versionRevision = self::id($input['expected_version_revision'] ?? null);
        $id = self::id($input['id'] ?? 0, true);
        $title = self::title($input['title'] ?? null);
        return $this->transaction(function () use ($actor, $input, $courseId, $versionId, $versionRevision, $id, $title): int {
            $version = $this->draft($actor, $courseId, $versionId, $versionRevision);
            if ($id) {
                $module = $this->row('modules', $id, true);
                if ((int) $module->course_version_id !== $versionId) {
                    throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
                }
                if ((int) $module->revision !== self::id($input['revision'] ?? null)) {
                    throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
                }
                $this->update('modules', $module, ['title' => $title], $actor);
            } else {
                $modules = $this->listRows('modules', 'course_version_id', $versionId);
                if (count($modules) >= 1000) {
                    throw new \DomainException('COM_JLMS_OUTLINE_LIMIT');
                }
                $order = $modules ? max(array_map(static fn ($m): int => (int) $m->ordering, $modules)) + 1 : 0;
                $id = $this->insert('modules', ['course_version_id' => $versionId, 'logical_key' => self::uuid(),
                    'title' => $title, 'ordering' => $order, 'params_json' => '{}'], $actor);
            }
            $this->update('course_versions', $version, [], $actor);
            return $id;
        });
    }

    public function providers(): array
    {
        return ($this->registryFactory)()->descriptors();
    }

    public function videoProvider(string $key): \Jlms\Contracts\Video\VideoProviderInterface
    {
        return ($this->registryFactory)()->get($key);
    }

    public static function duration(mixed $value): ?int
    {
        if ($value === '' || $value === null) { return null; }
        if (!is_string($value) && !is_int($value)) { throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT'); }
        if (!preg_match('/\A([0-9]{1,5})(?:\.([0-9]{1,3}))?\z/D', (string) $value, $parts)) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
        }
        $ms = (int) $parts[1] * 1000 + (int) str_pad($parts[2] ?? '', 3, '0');
        if ($ms < 1000 || $ms > 28800000) { throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT'); }
        return $ms;
    }

    public function saveLesson(User $actor, array $input): int
    {
        $courseId = self::id($input['course_id'] ?? null);
        $versionId = self::id($input['version_id'] ?? null);
        $versionRevision = self::id($input['expected_version_revision'] ?? null);
        $moduleId = self::id($input['module_id'] ?? null);
        $id = self::id($input['id'] ?? 0, true);
        $title = self::title($input['title'] ?? null);
        $kind = $input['kind'] ?? null;
        if (!in_array($kind, ['text', 'video'], true) || !is_string($input['body'] ?? '')
            || strlen($input['body'] ?? '') > 1048576) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
        }
        if (!is_string($input['required'] ?? '1') && !is_int($input['required'] ?? 1)) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
        }
        $required = (string) ($input['required'] ?? '1');
        if (!in_array($required, ['0', '1'], true)) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
        }
        $fields = ['module_id' => $moduleId, 'title' => $title, 'kind' => $kind,
            'body' => ($this->filterHtml)($input['body'] ?? ''), 'required' => (int) $required,
            'completion_rule' => $kind === 'video' ? 'video_coverage' : 'manual', 'provider_key' => null,
            'source_json' => null, 'media_asset_id' => null, 'duration_ms' => null];
        if ($kind === 'video' && ($input['provider_key'] ?? '') === 'html5') {
            if (($input['source_reference'] ?? '') !== '') { throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT'); }
            $fields['provider_key'] = 'html5';
        } elseif ($kind === 'video') {
            $fields['duration_ms'] = self::duration($input['duration_seconds'] ?? null);
            $fields['params_json'] = json_encode(['video_duration_confirmed' => ($input['duration_confirmed'] ?? '') === '1'], JSON_THROW_ON_ERROR);
            if (!is_string($input['provider_key'] ?? null) || !is_string($input['source_reference'] ?? null)
                || !is_string($input['privacy_hash'] ?? '')) {
                throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
            }
            $adapter = ($this->registryFactory)()->get($input['provider_key']);
            $options = empty($input['privacy_hash']) ? [] : ['privacy_hash' => $input['privacy_hash']];
            $source = $adapter->normaliseSource(new SourceInput($input['source_reference'], $options));
            if ($source->providerKey !== $input['provider_key']) {
                throw new \DomainException('COM_JLMS_SOURCE_INVALID');
            }
            $fields['provider_key'] = $source->providerKey;
            $fields['source_json'] = json_encode(['_schema' => 1, 'provider' => $source->providerKey,
                'reference' => $source->reference, 'options' => $source->options], JSON_THROW_ON_ERROR);
        }
        return $this->transaction(function () use ($actor, $input, $courseId, $versionId, $versionRevision, $moduleId, $id, $fields): int {
            $version = $this->draft($actor, $courseId, $versionId, $versionRevision);
            $module = $this->row('modules', $moduleId, true);
            if ((int) $module->course_version_id !== $versionId) {
                throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
            }
            if ($id) {
                $lesson = $this->row('lessons', $id, true);
                if ((int) $lesson->course_version_id !== $versionId) {
                    throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
                }
                if ((int) $lesson->revision !== self::id($input['revision'] ?? null)) {
                    throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
                }
                if (!in_array($lesson->kind, ['text', 'video'], true)) {
                    throw new \DomainException('COM_JLMS_LESSON_KIND_NOT_AVAILABLE');
                }
                if ((int) $lesson->module_id !== $moduleId) {
                    throw new \DomainException('COM_JLMS_MOVE_MODULE_NOT_AVAILABLE');
                }
                if ($fields['kind'] === 'video' && $fields['provider_key'] === 'html5' && $lesson->provider_key === 'html5') {
                    foreach (['source_json', 'media_asset_id', 'duration_ms', 'params_json'] as $field) { $fields[$field] = $lesson->$field; }
                }
                $changedSource = $lesson->kind !== $fields['kind'] || $lesson->provider_key !== $fields['provider_key']
                    || $lesson->source_json !== $fields['source_json'] || $lesson->duration_ms != $fields['duration_ms'];
                $fields['source_revision'] = (int) $lesson->source_revision + (int) $changedSource;
                $this->update('lessons', $lesson, $fields, $actor);
            } else {
                $q = $this->db->createQuery()->select('COUNT(*)')->from($this->db->quoteName('#__jlms_lessons'))
                    ->where('course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER);
                if ((int) $this->db->setQuery($q)->loadResult() >= 5000) {
                    throw new \DomainException('COM_JLMS_OUTLINE_LIMIT');
                }
                $lessons = $this->listRows('lessons', 'module_id', $moduleId, 5001);
                $order = $lessons ? max(array_map(static fn ($l): int => (int) $l->ordering, $lessons)) + 1 : 0;
                $id = $this->insert('lessons', $fields + ['course_version_id' => $versionId, 'logical_key' => self::uuid(),
                    'alias' => \Joomla\String\StringHelper::substr(ApplicationHelper::stringURLSafe($fields['title']), 0, 140) ?: 'lesson',
                    'ordering' => $order, 'source_revision' => 1, 'preview' => 0,
                    'params_json' => '{}', 'fields_snapshot_json' => '{}'], $actor);
            }
            $this->update('course_versions', $version, [], $actor);
            return $id;
        });
    }

    public function deleteItem(User $actor, string $kind, array $input): void
    {
        $table = match ($kind) { 'module' => 'modules', 'lesson' => 'lessons', default => throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT') };
        $courseId = self::id($input['course_id'] ?? null);
        $versionId = self::id($input['version_id'] ?? null);
        $id = self::id($input['id'] ?? null);
        $this->transaction(function () use ($actor, $input, $courseId, $versionId, $id, $table, $kind): void {
            $version = $this->draft($actor, $courseId, $versionId, self::id($input['expected_version_revision'] ?? null), 'core.delete');
            $row = $this->row($table, $id, true);
            if ((int) $row->course_version_id !== $versionId) {
                throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
            }
            if ((int) $row->revision !== self::id($input['revision'] ?? null) || ($input['confirmation'] ?? '') !== '1') {
                throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
            }
            $dependencies = $kind === 'module' ? [['lessons', 'module_id']]
                : [['attachments', 'lesson_id'], ['quizzes', 'lesson_id'], ['prerequisites', 'lesson_id'], ['prerequisites', 'required_lesson_id']];
            foreach ($dependencies as [$related, $column]) {
                $q = $this->db->createQuery()->select('COUNT(*)')->from($this->db->quoteName('#__jlms_' . $related))
                    ->where($this->db->quoteName($column) . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
                if ((int) $this->db->setQuery($q)->loadResult()) {
                    throw new \DomainException('COM_JLMS_ITEM_HAS_DEPENDENCIES');
                }
            }
            $q = $this->db->createQuery()->delete($this->db->quoteName('#__jlms_' . $table))
                ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($q)->execute();
            $this->update('course_versions', $version, [], $actor);
        });
    }

    public function reorder(User $actor, string $kind, array $input): void
    {
        $courseId = self::id($input['course_id'] ?? null);
        $versionId = self::id($input['version_id'] ?? null);
        $id = self::id($input['id'] ?? null);
        $direction = $input['direction'] ?? null;
        if (!in_array($kind, ['module', 'lesson'], true) || !in_array($direction, ['up', 'down'], true)) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
        }
        $this->transaction(function () use ($actor, $input, $courseId, $versionId, $id, $direction, $kind): void {
            $version = $this->draft($actor, $courseId, $versionId, self::id($input['expected_version_revision'] ?? null));
            $item = $this->row($kind === 'module' ? 'modules' : 'lessons', $id, true);
            if ((int) $item->course_version_id !== $versionId) {
                throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
            }
            $table = $kind === 'module' ? 'modules' : 'lessons';
            $items = $this->listRows($table, $kind === 'module' ? 'course_version_id' : 'module_id',
                $kind === 'module' ? $versionId : (int) $item->module_id, 5001);
            $ids = array_map(static fn ($row): int => (int) $row->id, $items);
            $position = array_search($id, $ids, true);
            $target = $position + ($direction === 'up' ? -1 : 1);
            if ($position === false || $target < 0 || $target >= count($items)) {
                return;
            }
            [$items[$position], $items[$target]] = [$items[$target], $items[$position]];
            foreach ($items as $order => $row) {
                if ((int) $row->ordering !== $order) {
                    $this->update($table, $row, ['ordering' => $order], $actor);
                }
            }
            $this->update('course_versions', $version, [], $actor);
        });
    }
}
