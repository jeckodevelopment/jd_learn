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
        $actor = new User((int) $actor->id); $actor->clearAccessRights();
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
        if (($course->checked_out && (int) $course->checked_out !== (int) $actor->id)
            || ($version->checked_out && (int) $version->checked_out !== (int) $actor->id)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
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
        if (!empty($record->checked_out) && (int) $record->checked_out !== (int) $actor->id) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
        $update = (object) (['id' => (int) $record->id] + $fields + [
            'revision' => (int) $record->revision + 1, 'modified_by' => (int) $actor->id,
            'modified_at' => gmdate('Y-m-d H:i:s')]);
        $this->db->updateObject('#__jlms_' . $table, $update, 'id', true);
    }

    private function listRows(string $table, string $field, int $id, int $limit = 1001): array
    {
        $columns = $table === 'modules'
            ? ['id', 'course_version_id', 'title', 'ordering', 'revision']
            : ['id', 'course_version_id', 'module_id', 'title', 'kind', 'provider_key', 'media_asset_id', 'required', 'ordering', 'revision', 'checked_out'];
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
        $q = $this->db->createQuery()->select('id, course_id, version_no, status, revision, checked_out')
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
            if ($course->checked_out && (int) $course->checked_out !== (int) $actor->id) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            $q = $this->db->createQuery()->select('id')->from($this->db->quoteName('#__jlms_course_versions'))
                ->where('course_id = :course')->where('status = :status');
            $status = 'draft';
            $q->bind(':course', $courseId, ParameterType::INTEGER)->bind(':status', $status);
            $q->order('version_no DESC, id DESC');
            $existing = (int) $this->db->setQuery($q)->loadResult();
            if ($existing) {
                return $existing;
            }
            if ((int) $course->revision !== $expectedRevision) {
                throw new \DomainException('COM_JLMS_REVISION_CONFLICT');
            }
            $query = $this->db->createQuery()->select('COALESCE(MAX(version_no), 0)')
                ->from('#__jlms_course_versions')->where('course_id = :course')->bind(':course', $courseId, ParameterType::INTEGER);
            $number = (int) $this->db->setQuery($query)->loadResult() + 1;
            if ($course->current_version_id !== null) {
                $source = $this->row('course_versions', (int) $course->current_version_id);
                if ((int) $source->course_id !== $courseId || $source->status !== 'published') {
                    throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE');
                }
                $id = (new CourseVersionCopy($this->db))->copy($actor, $source, $number);
                $this->update('courses', $course, [], $actor);
                return $id;
            }
            $id = $this->insert('course_versions', ['course_id' => $courseId, 'version_no' => $number,
                'status' => 'draft', 'title_snapshot' => $course->title,
                'rules_json' => '{"_schema":1,"mode":"learning","sequence_mode":"free","video_threshold_basis_points":9000,"certificate_enabled":false,"completion_policy":"all_required"}',
                'presentation_json' => CoursePresentation::snapshot($course),
                'fields_snapshot_json' => '{}'], $actor);
            $this->update('courses', $course, [], $actor);
            return $id;
        });
    }

    /** Recover a frozen programme as a separate draft; never replace other drafts or learning records. */
    public function restoreVersion(User $identity, array $input): int
    {
        if (!in_array($input['confirmation'] ?? null, ['1', 1], true)) { throw new \DomainException('COM_JLMS_PROGRAMME_RESTORE_CONFIRM_REQUIRED'); }
        $courseId = self::id($input['course_id'] ?? null); $sourceId = self::id($input['version_id'] ?? null);
        $courseRevision = self::id($input['revision'] ?? null); $sourceRevision = self::id($input['expected_version_revision'] ?? null);
        return $this->transaction(function () use ($identity, $courseId, $sourceId, $courseRevision, $sourceRevision): int {
            $course = $this->row('courses', $courseId, true);
            $actor = new User((int)$identity->id); $actor->clearAccessRights();
            $this->authorise($actor, $course);
            $source = $this->row('course_versions', $sourceId, true);
            if ((int)$source->course_id !== $courseId) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
            if (($course->checked_out && (int)$course->checked_out !== (int)$actor->id)
                || ($source->checked_out && (int)$source->checked_out !== (int)$actor->id)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            if ((int)$course->revision !== $courseRevision || (int)$source->revision !== $sourceRevision) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
            if ($course->current_version_id === null || (int)$course->current_version_id === $sourceId
                || !in_array($source->status, ['published', 'retired'], true) || !$source->published_at
                || !is_string($source->structure_hash) || preg_match('/\A[a-f0-9]{64}\z/D', $source->structure_hash) !== 1) {
                throw new \DomainException('COM_JLMS_PROGRAMME_RESTORE_SOURCE_INVALID');
            }
            $query = $this->db->createQuery()->select('COALESCE(MAX(version_no), 0)')->from('#__jlms_course_versions')
                ->where('course_id=:course')->bind(':course', $courseId, ParameterType::INTEGER);
            $number = (int)$this->db->setQuery($query)->loadResult() + 1;
            $target = (new CourseVersionCopy($this->db))->copy($actor, $source, $number);
            $this->update('courses', $course, [], $actor);
            (new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($this->db))->record($actor,
                ['id'=>$courseId, 'title'=>$course->title, 'source_version_id'=>$sourceId, 'target_version_id'=>$target,
                 'source_version_no'=>(int)$source->version_no, 'target_version_no'=>$number,
                 'link'=>'index.php?option=com_jlms&view=curriculum&id='.$courseId.'&version_id='.$target,
                 'correlation_id'=>bin2hex(random_bytes(16))], 'programmerestore');
            return $target;
        });
    }

    public function teachingRules(User $actor, int $courseId, int $versionId): array
    {
        $course = $this->row('courses', $courseId);
        $this->authorise($actor, $course);
        $version = $this->row('course_versions', $versionId);
        if ((int)$version->course_id !== $courseId) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
        return \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode($version->rules_json);
    }

    public function saveTeachingRules(User $actor, array $input): void
    {
        $course = self::id($input['course_id'] ?? null); $version = self::id($input['version_id'] ?? null);
        $revision = self::id($input['expected_version_revision'] ?? null);
        $this->transaction(function () use ($actor, $course, $version, $revision, $input): void {
            $record = $this->draft($actor, $course, $version, $revision);
            $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode($record->rules_json);
            $rules['video_threshold_basis_points'] = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::basisPoints($input['video_threshold'] ?? null);
            $this->update('course_versions', $record, ['rules_json'=>json_encode($rules, JSON_THROW_ON_ERROR)], $actor);
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

    public function duplicateItem(User $identity, string $kind, array $input): int
    {
        $actor = CourseEditorAccess::fresh($identity);
        $table = match ($kind) { 'module' => 'modules', 'lesson' => 'lessons', default => throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT') };
        $courseId = self::id($input['course_id'] ?? null);
        $versionId = self::id($input['version_id'] ?? null);
        $id = self::id($input['id'] ?? null);
        $title = self::title($input['title'] ?? null);
        return $this->transaction(function () use ($actor, $kind, $table, $input, $courseId, $versionId, $id, $title): int {
            $version = $this->draft($actor, $courseId, $versionId, self::id($input['expected_version_revision'] ?? null));
            $course = $this->row('courses', $courseId);
            if (($course->checked_out && (int) $course->checked_out !== (int) $actor->id)
                || ($version->checked_out && (int) $version->checked_out !== (int) $actor->id)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            $source = $this->row($table, $id, true);
            if ((int) $source->course_version_id !== $versionId) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
            if ((int) $source->revision !== self::id($input['revision'] ?? null)) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
            $moduleId = $kind === 'lesson' ? self::id($input['module_id'] ?? null) : 0;
            if ($moduleId && (int) $this->row('modules', $moduleId)->course_version_id !== $versionId) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
            $newId = (new CourseVersionCopy($this->db))->item($actor, $kind, $source, $versionId, $title, $moduleId);
            $this->update('course_versions', $version, [], $actor);
            return $newId;
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

    /** Read-only authoring scope; no remote request or long transaction before fresh authorisation. */
    public function durationFingerprint(User $actor, array $input): string
    {
        $course = $this->row('courses', self::id($input['course_id'] ?? null)); $this->authorise($actor, $course);
        $version = $this->row('course_versions', self::id($input['version_id'] ?? null));
        $module = $this->row('modules', self::id($input['module_id'] ?? null));
        if ((int)$version->course_id !== (int)$course->id || (int)$module->course_version_id !== (int)$version->id) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
        if ($version->status !== 'draft') { throw new \DomainException('COM_JLMS_VERSION_IMMUTABLE'); }
        if ((int)$version->revision !== self::id($input['expected_version_revision'] ?? null)) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
        if (($course->checked_out && (int)$course->checked_out !== (int)$actor->id) || ($version->checked_out && (int)$version->checked_out !== (int)$actor->id)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
        $id = self::id($input['id'] ?? 0, true); $revision = self::id($input['revision'] ?? 1);
        if ($id) {
            $lesson = $this->row('lessons', $id);
            if ((int)$lesson->course_version_id !== (int)$version->id || (int)$lesson->module_id !== (int)$module->id) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
            if ((int)$lesson->revision !== $revision) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
            if ($lesson->checked_out && (int)$lesson->checked_out !== (int)$actor->id) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
        }
        $provider = $input['provider_key'] ?? null;
        if (!is_string($provider) || !in_array($provider, ['youtube','vimeo','cloudflarestream'], true)
            || !is_string($input['source_reference'] ?? null) || !is_string($input['privacy_hash'] ?? '')) { throw new \DomainException('COM_JLMS_SOURCE_INVALID'); }
        $source = ($this->registryFactory)()->get($provider)->normaliseSource(new SourceInput($input['source_reference'], empty($input['privacy_hash']) ? [] : ['privacy_hash'=>$input['privacy_hash']]));
        return hash('sha256', json_encode([(int)$actor->id,(int)$course->id,(int)$version->id,(int)$version->revision,(int)$module->id,$id,$revision,$source->providerKey,$source->reference,$source->options], JSON_THROW_ON_ERROR));
    }

    public function authoringMetadata(User $actor, array $input): array
    {
        $fingerprint = $this->durationFingerprint($actor, $input);
        $key = $input['provider_key']; $provider = ($this->registryFactory)()->get($key);
        if (!$provider->getDescriptor()->configured) { throw new \DomainException('COM_JLMS_VIDEO_EDITOR_CONFIG_REQUIRED'); }
        $policy = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked');
        if (!in_array($policy, ['embed','site_consent'], true)) { throw new \DomainException('COM_JLMS_VIDEO_EDITOR_POLICY_BLOCKED'); }
        $source = $provider->normaliseSource(new SourceInput($input['source_reference'], empty($input['privacy_hash']) ? [] : ['privacy_hash'=>$input['privacy_hash']]));
        $duration = null;
        if ($key === 'cloudflarestream') {
            $metadata = $provider->fetchMetadata($source, new \Jlms\Contracts\Video\MetadataContext((int)$actor->id, \Joomla\CMS\Factory::getApplication()->getLanguage()->getTag(), true));
            $duration = $metadata->durationMs;
            if (!\Jlms\Component\Jlms\Administrator\Domain\VideoDuration::valid($duration)) { throw new \DomainException('COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED'); }
        }
        if (!hash_equals($fingerprint, $this->durationFingerprint($actor, $input))) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
        return ['fingerprint'=>$fingerprint,'duration_ms'=>$duration,'origin'=>$duration === null ? 'author_sdk' : 'provider_api',
            'provider'=>$key,'options'=>['reference'=>$source->reference,'locale'=>\Joomla\CMS\Factory::getApplication()->getLanguage()->getTag()] + $source->options];
    }

    public function saveLesson(User $actor, array $input, ?array $durationProof = null): int
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
            // HTML5 source/duration are assigned only by LocalVideoService; ignore stale or forged form values.
            $fields['provider_key'] = 'html5';
        } elseif ($kind === 'video') {
            $mode = $input['duration_mode'] ?? 'legacy';
            if (!in_array($mode, ['auto','manual','legacy'], true)) { throw new \DomainException('COM_JLMS_INVALID_INPUT'); }
            $duration = $mode === 'auto' ? null : self::duration($input['duration_seconds'] ?? null);
            $origin = $mode === 'auto' ? 'unmeasured' : 'declared'; $confirmed = $mode === 'manual' ? $duration !== null : ($mode === 'legacy' && ($input['duration_confirmed'] ?? '') === '1');
            if ($durationProof !== null) {
                if ($mode !== 'auto' || !hash_equals($this->durationFingerprint($actor, $input), (string)($durationProof['fingerprint'] ?? ''))
                    || !\Jlms\Component\Jlms\Administrator\Domain\VideoDuration::valid($durationProof['duration_ms'] ?? null)
                    || !in_array($durationProof['origin'] ?? '', ['author_sdk','provider_api'], true)) { throw new \DomainException('COM_JLMS_VIDEO_METADATA_STALE'); }
                $duration = $durationProof['duration_ms']; $origin = $durationProof['origin']; $confirmed = true;
            }
            $fields['duration_ms'] = $duration;
            $fields['params_json'] = json_encode(['video_duration_confirmed'=>$confirmed, 'video_duration_origin'=>$origin], JSON_THROW_ON_ERROR);
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
        return $this->transaction(function () use ($actor, $input, $courseId, $versionId, $versionRevision, $moduleId, $id, $fields, $durationProof): int {
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
                if ($fields['kind'] === 'video' && $fields['provider_key'] !== 'html5' && ($input['duration_mode'] ?? '') === 'auto'
                    && $durationProof === null && $lesson->provider_key === $fields['provider_key'] && $lesson->source_json === $fields['source_json']) {
                    $fields['duration_ms'] = $lesson->duration_ms; $fields['params_json'] = $lesson->params_json;
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

    /** Exact sibling permutation, saved atomically against the current draft revision. */
    public function saveOrder(User $identity, array $input): int
    {
        $actor = CourseEditorAccess::fresh($identity);
        if (array_key_exists('order_json', $input)) {
            if (!is_string($input['order_json']) || strlen($input['order_json']) > 65536) { throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT'); }
            try { $input['order'] = json_decode($input['order_json'], true, 16, JSON_THROW_ON_ERROR); }
            catch (\JsonException) { throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT'); }
        }
        $kind = $input['kind'] ?? '';
        if (!in_array($kind, ['module', 'lesson'], true) || !is_array($input['order'] ?? null)
            || !array_is_list($input['order']) || count($input['order']) > ($kind === 'module' ? 1000 : 5000)) { throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT'); }
        $course = self::id($input['course_id'] ?? null); $versionId = self::id($input['version_id'] ?? null);
        $revision = self::id($input['expected_version_revision'] ?? null);
        $order = array_map(static fn($id): int => self::id($id), $input['order']);
        if (count(array_unique($order)) !== count($order)) { throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT'); }
        return $this->transaction(function () use ($actor, $input, $kind, $course, $versionId, $revision, $order): int {
            $version = $this->draft($actor, $course, $versionId, $revision);
            $module = $kind === 'lesson' ? self::id($input['module_id'] ?? null) : $versionId;
            if ($kind === 'lesson' && (int) $this->row('modules', $module)->course_version_id !== $versionId) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
            $table = $kind === 'module' ? 'modules' : 'lessons';
            $items = $this->listRows($table, $kind === 'module' ? 'course_version_id' : 'module_id', $module, $kind === 'module' ? 1001 : 5001);
            $ids = array_map(static fn($row): int => (int) $row->id, $items); $sorted = $ids; $wanted = $order;
            sort($sorted, SORT_NUMERIC); sort($wanted, SORT_NUMERIC);
            if ($sorted !== $wanted) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
            $byId = []; foreach ($items as $item) { $byId[(int) $item->id] = $item; }
            $changed = false;
            foreach ($order as $position => $id) {
                if ((int) $byId[$id]->ordering !== $position) { $this->update($table, $byId[$id], ['ordering' => $position], $actor); $changed = true; }
            }
            if ($changed) { $this->update('course_versions', $version, [], $actor); }
            return (int) $version->revision + (int) $changed;
        });
    }

    /** Move a lesson, including quiz/file references, without changing its identity or source revision. */
    public function moveLesson(User $identity, array $input): int
    {
        $actor = CourseEditorAccess::fresh($identity);
        $course = self::id($input['course_id'] ?? null); $versionId = self::id($input['version_id'] ?? null);
        $id = self::id($input['id'] ?? null); $destination = self::id($input['module_id'] ?? null);
        return $this->transaction(function () use ($actor, $input, $course, $versionId, $id, $destination): int {
            $version = $this->draft($actor, $course, $versionId, self::id($input['expected_version_revision'] ?? null));
            $lesson = $this->row('lessons', $id, true); $module = $this->row('modules', $destination, true);
            if ((int) $lesson->course_version_id !== $versionId || (int) $module->course_version_id !== $versionId) { throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND'); }
            if ((int) $lesson->revision !== self::id($input['revision'] ?? null)) { throw new \DomainException('COM_JLMS_REVISION_CONFLICT'); }
            if ($lesson->kind === 'quiz' && !$actor->authorise('jlms.quiz.manage', 'com_jlms.course.' . $course)) { throw new \DomainException('JERROR_ALERTNOAUTHOR'); }
            if ((int) $lesson->module_id !== $destination) {
                $items = $this->listRows('lessons', 'module_id', $destination, 5001);
                $position = $items ? max(array_map(static fn($row): int => (int) $row->ordering, $items)) + 1 : 0;
                $this->update('lessons', $lesson, ['module_id' => $destination, 'ordering' => $position], $actor);
                $this->update('course_versions', $version, [], $actor);
            }
            return $id;
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
