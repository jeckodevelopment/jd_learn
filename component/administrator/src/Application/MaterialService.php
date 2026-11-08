<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage;

final class MaterialService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning,
        private readonly ProtectedMaterialStorage $storage)
    {
    }

    private function row(string $table, int $id, bool $lock = false): object
    {
        $q = $this->db->createQuery()->select('*')->from('#__jlms_' . $table)->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
        if ($lock) { $q->setQuery((string) $q . ' FOR UPDATE'); }
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
    }

    private function author(User $identity, int $courseId, int $versionId, bool $lock = false): array
    {
        $actor = $this->learning->freshActor($identity);
        $course = $this->row('courses', $courseId, $lock);
        $version = $this->row('course_versions', $versionId, $lock);
        if (!$actor->authorise('core.manage', 'com_jlms') || !$actor->authorise('jlms.materials.manage', 'com_jlms.course.' . $courseId)) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        if ((int) $version->course_id !== $courseId) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        return [$actor, $course, $version];
    }

    private function transaction(\Closure $work): mixed
    {
        $this->db->transactionStart();
        try { $result = $work(); $this->db->transactionCommit(); return $result; }
        catch (\Throwable $error) { $this->db->transactionRollback(); throw $error; }
    }

    private function draft(object $version, int $expected): void
    {
        if ($version->status !== 'draft') { throw new \RuntimeException('COM_JLMS_VERSION_IMMUTABLE', 409); }
        if ((int) $version->revision !== $expected) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
    }

    private function touch(object $version, User $actor): void
    {
        $update = (object) ['id' => (int) $version->id, 'revision' => (int) $version->revision + 1,
            'modified_by' => (int) $actor->id, 'modified_at' => gmdate('Y-m-d H:i:s')];
        $this->db->updateObject('#__jlms_course_versions', $update, 'id');
    }

    private function listing(int $versionId, ?int $lessonId, int $page, bool $admin): array
    {
        $page = max(0, min(10000, $page));
        $q = $this->db->createQuery()->from('#__jlms_attachments AS a')
            ->innerJoin('#__jlms_assets AS f ON f.id = a.asset_id')->where('a.course_version_id = :version')
            ->bind(':version', $versionId, ParameterType::INTEGER);
        if ($lessonId === null) {
            $q->where('a.lesson_id IS NULL');
        } elseif ($lessonId > 0) {
            $q->where('a.lesson_id = :lesson')->bind(':lesson', $lessonId, ParameterType::INTEGER);
        }
        if (!$admin) {
            $now = gmdate('Y-m-d H:i:s');
            $q->where("a.state = 1 AND a.preview = 0 AND f.status = 'ready'")
                ->where('(a.publish_up IS NULL OR a.publish_up <= :nowUp) AND (a.publish_down IS NULL OR a.publish_down > :nowDown)')
                ->bind(':nowUp', $now)->bind(':nowDown', $now);
        }
        $count = clone $q;
        $total = (int) $this->db->setQuery($count->select('COUNT(*)'))->loadResult();
        $q->select('a.id, a.title, a.description, a.lesson_id, a.ordering, a.state, a.preview, a.publish_up, a.publish_down, a.revision, f.original_name, f.mime_type, f.byte_size, f.status')
            ->order('a.ordering, a.id');
        return ['items' => $this->db->setQuery($q, $page * 20, 20)->loadObjectList(), 'total' => $total, 'page' => $page, 'limit' => 20];
    }

    public function administration(User $actor, int $courseId, int $versionId, int $page = 0): array
    {
        [, $course, $version] = $this->author($actor, $courseId, $versionId);
        $q = $this->db->createQuery()->select('id, title')->from('#__jlms_lessons')->where('course_version_id = :version')
            ->bind(':version', $versionId, ParameterType::INTEGER)->order('module_id, ordering, id');
        return ['course' => $course, 'version' => $version, 'files' => $this->listing($versionId, 0, $page, true),
            'lessons' => $this->db->setQuery($q, 0, 5000)->loadObjectList(), 'limit' => $this->storage->limit(),
            'scan_required' => (bool) $this->storage->config()->get('material_scan_required', false),
            'schedule_timezone' => CourseSchedule::read($course)['timezone']];
    }

    /** Uploaded binary is validated/scanned before the short authoring transaction. */
    public function upload(User $identity, int $courseId, int $versionId, int $lessonId, int $expected, string $title,
        string $description, array $upload, int $replaceId = 0, mixed $publishUp = null, mixed $publishDown = null): int
    {
        [, $course, $version] = $this->author($identity, $courseId, $versionId);
        $this->draft($version, $expected);
        $availability = MaterialAvailability::normalize($publishUp, $publishDown, CourseSchedule::read($course)['timezone']);
        if (in_array($upload['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new \RuntimeException('COM_JLMS_FILE_SIZE', 413);
        }
        if (trim($title) === '' || preg_match('/\A[^\x00-\x1f]{1,255}\z/u', $title) !== 1 || strlen($description) > 4096
            || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($upload['tmp_name'] ?? null)
            || !is_string($upload['name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) {
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        if ($lessonId && (int) $this->row('lessons', $lessonId)->course_version_id !== $versionId) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $staged = $this->storage->stage($upload['tmp_name'], $upload['name']);
        $scan = $this->storage->scan((object) $staged);
        // A failed concurrent save may leave an unreferenced private blob; never delete a referenced blob speculatively.
        return $this->transaction(function () use ($identity, $courseId, $versionId, $lessonId, $expected, $title, $description, $staged, $scan, $replaceId, $availability): int {
            [$actor, , $version] = $this->author($identity, $courseId, $versionId, true);
            $this->draft($version, $expected);
            $now = gmdate('Y-m-d H:i:s');
            $asset = (object) ($staged + $scan + ['asset_revision' => 1, 'created_by' => (int) $actor->id,
                'modified_by' => (int) $actor->id, 'created_at' => $now, 'modified_at' => $now]);
            $this->db->insertObject('#__jlms_assets', $asset, 'id');
            if ($replaceId) {
                $attachment = $this->row('attachments', $replaceId, true);
                if ((int) $attachment->course_version_id !== $versionId || (int) $attachment->lesson_id !== $lessonId) {
                    throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
                }
                $update = (object) ['id' => $replaceId, 'asset_id' => (int) $asset->id, 'title' => trim($title),
                    'description' => $description, 'publish_up' => $availability['publish_up'], 'publish_down' => $availability['publish_down'],
                    'revision' => (int) $attachment->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => $now];
                $this->db->updateObject('#__jlms_attachments', $update, 'id');
                $id = $replaceId;
            } else {
                $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_attachments')->where('course_version_id = :version')
                    ->bind(':version', $versionId, ParameterType::INTEGER);
                $count = (int) $this->db->setQuery($q)->loadResult();
                if ($count >= 1000) { throw new \RuntimeException('COM_JLMS_OUTLINE_LIMIT', 409); }
                $attachment = (object) ['course_version_id' => $versionId, 'lesson_id' => $lessonId ?: null,
                    'asset_id' => (int) $asset->id, 'title' => trim($title), 'description' => $description, 'ordering' => $count,
                    'state' => 1, 'preview' => 0, 'publish_up' => $availability['publish_up'], 'publish_down' => $availability['publish_down'],
                    'revision' => 1, 'created_by' => (int) $actor->id,
                    'modified_by' => (int) $actor->id, 'created_at' => $now, 'modified_at' => $now];
                $this->db->insertObject('#__jlms_attachments', $attachment, 'id'); $id = (int) $attachment->id;
            }
            $this->touch($version, $actor);
            return $id;
        });
    }

    public function mutate(User $identity, int $courseId, int $versionId, int $id, int $expected, string $operation, array $input): void
    {
        $scan = [];
        $scannedId = 0;
        if ($operation === 'scan') {
            $this->author($identity, $courseId, $versionId);
            $link = $this->row('attachments', $id);
            if ((int) $link->course_version_id !== $versionId) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
            $asset = $this->row('assets', (int) $link->asset_id);
            $scan = $this->storage->scan($asset);
            $scannedId = (int) $asset->id;
        }
        $this->transaction(function () use ($identity, $courseId, $versionId, $id, $expected, $operation, $input, $scan, $scannedId): void {
            [$actor, $course, $version] = $this->author($identity, $courseId, $versionId, true);
            if ($operation === 'scan' && in_array($version->status, ['published', 'retired'], true)) {
                if ((int) $version->revision !== $expected) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
            } else { $this->draft($version, $expected); }
            $link = $this->row('attachments', $id, true);
            if ((int) $link->course_version_id !== $versionId) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
            if ($operation === 'delete') {
                $q = $this->db->createQuery()->delete('#__jlms_attachments')->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                $this->db->setQuery($q)->execute();
            } elseif ($operation === 'scan') {
                if ((int) $link->asset_id !== $scannedId) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
                $asset = (object) (['id' => (int) $link->asset_id] + $scan + ['modified_by' => (int) $actor->id, 'modified_at' => gmdate('Y-m-d H:i:s')]);
                $this->db->updateObject('#__jlms_assets', $asset, 'id');
            } elseif ($operation === 'save') {
                $title = $input['title'] ?? null;
                $description = $input['description'] ?? '';
                foreach (['state', 'ordering'] as $field) {
                    if (isset($input[$field]) && !is_int($input[$field]) && !is_string($input[$field])) {
                        throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
                    }
                }
                $state = (string) ($input['state'] ?? '1');
                $order = (string) ($input['ordering'] ?? '0');
                if (!is_string($title) || preg_match('/\A[^\x00-\x1f]{1,255}\z/u', trim($title)) !== 1
                    || !is_string($description) || strlen($description) > 4096 || !in_array($state, ['0', '1'], true)
                    || !ctype_digit($order) || strlen($order) > 5) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
                $availability = array_key_exists('publish_up', $input) || array_key_exists('publish_down', $input)
                    ? MaterialAvailability::normalize($input['publish_up'] ?? null, $input['publish_down'] ?? null, CourseSchedule::read($course)['timezone'])
                    : ['publish_up' => $link->publish_up, 'publish_down' => $link->publish_down];
                $update = (object) ['id' => $id, 'title' => trim($title), 'description' => $description, 'state' => (int) $state,
                    'publish_up' => $availability['publish_up'], 'publish_down' => $availability['publish_down'],
                    'ordering' => (int) $order, 'revision' => (int) $link->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => gmdate('Y-m-d H:i:s')];
                $this->db->updateObject('#__jlms_attachments', $update, 'id');
            } else { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
            if ($version->status === 'draft') { $this->touch($version, $actor); }
        });
    }

    public function learnerLists(User $actor, string $language, int $enrollmentId, int $lessonId, int $coursePage = 0, int $lessonPage = 0): array
    {
        $context = $this->learning->resourceContext($actor, $language, $enrollmentId, $lessonId);
        return ['course' => $this->listing($context['version_id'], null, $coursePage, false),
            'lesson' => $this->listing($context['version_id'], $lessonId, $lessonPage, false)];
    }

    public function download(User $actor, string $language, int $enrollmentId, int $id): array
    {
        $actor = $this->learning->freshActor($actor);
        $link = $this->row('attachments', $id);
        $context = $this->learning->resourceContext($actor, $language, $enrollmentId, (int) $link->lesson_id);
        if ((int) $link->course_version_id !== $context['version_id'] || (int) $link->state !== 1 || (int) $link->preview !== 0) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $now = gmdate('Y-m-d H:i:s.u');
        if (($link->publish_up !== null && $link->publish_up > $now) || ($link->publish_down !== null && $link->publish_down <= $now)) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        return $this->open((int) $link->asset_id);
    }

    public function adminDownload(User $actor, int $courseId, int $versionId, int $id): array
    {
        $this->author($actor, $courseId, $versionId);
        $link = $this->row('attachments', $id);
        if ((int) $link->course_version_id !== $versionId) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        return $this->open((int) $link->asset_id);
    }

    private function open(int $assetId): array
    {
        if (!(bool) $this->storage->config()->get('material_php_delivery', false)) {
            throw new \RuntimeException('COM_JLMS_DELIVERY_DISABLED', 503);
        }
        $asset = $this->row('assets', $assetId);
        if ($asset->status !== 'ready') { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        if ((bool) $this->storage->config()->get('material_scan_required', false)) {
            $scan = json_decode($asset->scan_json ?? '{}', true);
            if (($scan['engine'] ?? '') !== 'clamd' || ($scan['result'] ?? '') !== 'clean') {
                throw new \RuntimeException('COM_JLMS_MATERIAL_NOT_READY', 503);
            }
        }
        return ['handle' => $this->storage->open($asset), 'size' => (int) $asset->byte_size,
            'name' => $asset->original_name, 'mime' => $asset->mime_type, 'etag' => '"' . $asset->sha256 . '"'];
    }
}
