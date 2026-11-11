<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage;
use Jlms\Component\Jlms\Administrator\Domain\MaterialAvailability;

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

    /** Explicitly published course-level samples for the current published version only. */
    public function publicPreviewList(int $courseId, int $versionId, int $page = 0): array
    {
        $page = max(0, min(10000, $page));
        $now = gmdate('Y-m-d H:i:s');
        $query = $this->db->createQuery()->from('#__jlms_attachments AS a')
            ->innerJoin('#__jlms_assets AS f ON f.id = a.asset_id')
            ->innerJoin('#__jlms_course_versions AS v ON v.id = a.course_version_id AND v.course_id = :course AND v.status = :published')
            ->innerJoin('#__jlms_courses AS c ON c.id = v.course_id AND c.current_version_id = v.id AND c.state = 1')
            ->where('a.course_version_id = :version')->where('a.lesson_id IS NULL')->where('a.preview = 1 AND a.state = 1')
            ->where("f.status = 'ready'")
            ->where('(a.publish_up IS NULL OR a.publish_up <= :nowUp) AND (a.publish_down IS NULL OR a.publish_down > :nowDown)')
            ->bind(':course', $courseId, ParameterType::INTEGER)->bind(':version', $versionId, ParameterType::INTEGER)
            ->bind(':published', 'published')->bind(':nowUp', $now)->bind(':nowDown', $now);
        $total = (int) $this->db->setQuery((clone $query)->select('COUNT(*)'))->loadResult();
        $items = $this->db->setQuery((clone $query)->select('a.id, a.title, a.description, a.ordering, a.publish_up, a.publish_down, f.original_name, f.mime_type, f.byte_size')
            ->order('a.ordering, a.id'), $page * 20, 20)->loadObjectList();
        return ['items' => $items, 'total' => $total, 'page' => $page, 'limit' => 20];
    }

    /** Public access is additionally gated by CatalogService in the site controller. */
    public function publicPreviewDownload(int $courseId, int $versionId, int $attachmentId): array
    {
        $query = $this->db->createQuery()->select('a.*, f.status AS asset_status, f.original_name, f.mime_type, f.byte_size')
            ->from('#__jlms_attachments AS a')->innerJoin('#__jlms_assets AS f ON f.id = a.asset_id')
            ->innerJoin('#__jlms_course_versions AS v ON v.id = a.course_version_id AND v.course_id = :course AND v.status = :published')
            ->innerJoin('#__jlms_courses AS c ON c.id = v.course_id AND c.current_version_id = v.id AND c.state = 1')
            ->where('a.id = :id')->where('a.course_version_id = :version')->where('a.lesson_id IS NULL')
            ->where('a.preview = 1 AND a.state = 1')->where("f.status = 'ready'")
            ->where('(a.publish_up IS NULL OR a.publish_up <= :nowUp) AND (a.publish_down IS NULL OR a.publish_down > :nowDown)')
            ->bind(':course', $courseId, ParameterType::INTEGER)->bind(':version', $versionId, ParameterType::INTEGER)
            ->bind(':id', $attachmentId, ParameterType::INTEGER)->bind(':published', 'published')
            ->bind(':nowUp', gmdate('Y-m-d H:i:s'))->bind(':nowDown', gmdate('Y-m-d H:i:s'));
        $link = $this->db->setQuery($query)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        $file = $this->open((int) $link->asset_id);
        $file['inline'] = in_array($file['mime'], ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true);
        return $file;
    }

    /** Available course/lesson files for an authorised, non-enrolled editorial preview. */
    public function previewLists(User $identity, int $courseId, int $versionId, int $lessonId): array
    {
        $this->author($identity, $courseId, $versionId);
        $lesson = $this->row('lessons', $lessonId);
        if ((int) $lesson->course_version_id !== $versionId || !in_array($lesson->kind, ['document', 'audio'], true)) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        return ['course' => $this->listing($versionId, null, 0, false),
            'lesson' => $this->listing($versionId, $lessonId, 0, false)];
    }

    /** Serve only an attachment linked to this course version and the selected lesson/course. */
    public function previewDownload(User $identity, int $courseId, int $versionId, int $lessonId, int $attachmentId): array
    {
        $this->author($identity, $courseId, $versionId);
        $lesson = $this->row('lessons', $lessonId);
        $link = $this->row('attachments', $attachmentId);
        if ((int) $lesson->course_version_id !== $versionId || !in_array($lesson->kind, ['document', 'audio'], true)
            || (int) $link->course_version_id !== $versionId
            || ($link->lesson_id !== null && (int) $link->lesson_id !== $lessonId)
            || (int) $link->state !== 1 || (int) $link->preview !== 0) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $now = gmdate('Y-m-d H:i:s');
        if (($link->publish_up !== null && $link->publish_up > $now) || ($link->publish_down !== null && $link->publish_down <= $now)) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $file = $this->open((int) $link->asset_id);
        if ($lesson->kind === 'audio' && !in_array($file['mime'], ['audio/mpeg', 'audio/ogg'], true)) {
            fclose($file['handle']);
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $file['inline'] = $lesson->kind === 'audio'
            || in_array($file['mime'], ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'text/plain', 'text/csv'], true);
        return $file;
    }

    /** Uploaded binary is validated/scanned before the short authoring transaction. */
    public function upload(User $identity, int $courseId, int $versionId, int $lessonId, int $expected, string $title,
        string $description, array $upload, int $replaceId = 0, mixed $publishUp = null, mixed $publishDown = null, mixed $preview = 0): int
    {
        [, $course, $version] = $this->author($identity, $courseId, $versionId);
        $this->draft($version, $expected);
        $availability = MaterialAvailability::normalize($publishUp, $publishDown, CourseSchedule::read($course)['timezone']);
        if (!in_array((string) $preview, ['0', '1'], true)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $preview = (int) $preview;
        if ($preview === 1 && $lessonId > 0) { throw new \RuntimeException('COM_JLMS_PREVIEW_COURSE_SCOPE_ONLY', 422); }
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
        return $this->transaction(function () use ($identity, $courseId, $versionId, $lessonId, $expected, $title, $description, $staged, $scan, $replaceId, $availability, $preview): int {
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
                    'description' => $description, 'preview' => $preview, 'publish_up' => $availability['publish_up'], 'publish_down' => $availability['publish_down'],
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
                    'state' => 1, 'preview' => $preview, 'publish_up' => $availability['publish_up'], 'publish_down' => $availability['publish_down'],
                    'revision' => 1, 'created_by' => (int) $actor->id,
                    'modified_by' => (int) $actor->id, 'created_at' => $now, 'modified_at' => $now];
                $this->db->insertObject('#__jlms_attachments', $attachment, 'id'); $id = (int) $attachment->id;
            }
            $this->touch($version, $actor);
            return $id;
        });
    }

    /** Link an existing private asset to another scope in the same editable course version. */
    public function reuse(User $identity, int $courseId, int $versionId, int $expected, int $sourceId, int $lessonId): int
    {
        return $this->transaction(function () use ($identity, $courseId, $versionId, $expected, $sourceId, $lessonId): int {
            [$actor, , $version] = $this->author($identity, $courseId, $versionId, true);
            $this->draft($version, $expected);
            $source = $this->row('attachments', $sourceId, true);
            if ((int) $source->course_version_id !== $versionId) {
                throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            }
            if ($lessonId > 0 && (int) $this->row('lessons', $lessonId)->course_version_id !== $versionId) {
                throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            }
            $sourceAssetId = (int) $source->asset_id;
            $duplicate = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_attachments')
                ->where('course_version_id = :version')->where('asset_id = :asset')
                ->where('lesson_id ' . ($lessonId ? '= :lesson' : 'IS NULL'))
                ->bind(':version', $versionId, ParameterType::INTEGER)->bind(':asset', $sourceAssetId, ParameterType::INTEGER);
            if ($lessonId) { $duplicate->bind(':lesson', $lessonId, ParameterType::INTEGER); }
            if ((int) $this->db->setQuery($duplicate)->loadResult() > 0) {
                throw new \RuntimeException('COM_JLMS_MATERIAL_ALREADY_LINKED', 409);
            }
            $countQuery = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_attachments')->where('course_version_id = :version')
                ->bind(':version', $versionId, ParameterType::INTEGER);
            $count = (int) $this->db->setQuery($countQuery)->loadResult();
            if ($count >= 1000) { throw new \RuntimeException('COM_JLMS_OUTLINE_LIMIT', 409); }
            $now = gmdate('Y-m-d H:i:s');
            $link = (object) ['course_version_id' => $versionId, 'lesson_id' => $lessonId ?: null,
                'asset_id' => (int) $source->asset_id, 'title' => $source->title, 'description' => $source->description,
                'ordering' => $count, 'state' => (int) $source->state, 'preview' => 0,
                'publish_up' => $source->publish_up, 'publish_down' => $source->publish_down,
                'revision' => 1, 'created_by' => (int) $actor->id, 'modified_by' => (int) $actor->id,
                'created_at' => $now, 'modified_at' => $now];
            $this->db->insertObject('#__jlms_attachments', $link, 'id');
            $this->touch($version, $actor);
            return (int) $link->id;
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
                foreach (['state', 'ordering', 'preview'] as $field) {
                    if (isset($input[$field]) && !is_int($input[$field]) && !is_string($input[$field])) {
                        throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
                    }
                }
                $state = (string) ($input['state'] ?? '1');
                $order = (string) ($input['ordering'] ?? '0');
                $preview = (string) ($input['preview'] ?? '0');
                if (!is_string($title) || preg_match('/\A[^\x00-\x1f]{1,255}\z/u', trim($title)) !== 1
                    || !is_string($description) || strlen($description) > 4096 || !in_array($state, ['0', '1'], true)
                    || !in_array($preview, ['0', '1'], true) || !ctype_digit($order) || strlen($order) > 5) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
                if ($preview === '1' && $link->lesson_id !== null) { throw new \RuntimeException('COM_JLMS_PREVIEW_COURSE_SCOPE_ONLY', 422); }
                $availability = array_key_exists('publish_up', $input) || array_key_exists('publish_down', $input)
                    ? MaterialAvailability::normalize($input['publish_up'] ?? $link->publish_up, $input['publish_down'] ?? $link->publish_down, CourseSchedule::read($course)['timezone'])
                    : ['publish_up' => $link->publish_up, 'publish_down' => $link->publish_down];
                $update = (object) ['id' => $id, 'title' => trim($title), 'description' => $description, 'state' => (int) $state, 'preview' => (int) $preview,
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
        $now = gmdate('Y-m-d H:i:s');
        if (($link->publish_up !== null && $link->publish_up > $now) || ($link->publish_down !== null && $link->publish_down <= $now)) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        return $this->open((int) $link->asset_id);
    }

    /** Return only passive document/image formats for an authorised in-browser view. */
    public function inlinePreview(User $actor, string $language, int $enrollmentId, int $id): array
    {
        $file = $this->download($actor, $language, $enrollmentId, $id);
        if (!in_array($file['mime'], ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
            fclose($file['handle']);
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $file['inline'] = true;
        return $file;
    }

    public function audioDownload(User $actor, string $language, int $enrollmentId, int $lessonId, int $id): array
    {
        $actor = $this->learning->freshActor($actor); $link = $this->row('attachments', $id);
        $context = $this->learning->audioLessonContext($actor, $language, $enrollmentId, $lessonId);
        if ((int) $link->course_version_id !== $context['version_id'] || (int) $link->lesson_id !== $lessonId
            || (int) $link->state !== 1 || (int) $link->preview !== 0) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        $now = gmdate('Y-m-d H:i:s');
        if (($link->publish_up !== null && $link->publish_up > $now) || ($link->publish_down !== null && $link->publish_down <= $now)) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $file = $this->open((int) $link->asset_id);
        if (!in_array($file['mime'], ['audio/mpeg', 'audio/ogg'], true)) { fclose($file['handle']); throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        $file['inline'] = true;
        return $file;
    }

    public function adminDownload(User $actor, int $courseId, int $versionId, int $id): array
    {
        $this->author($actor, $courseId, $versionId);
        $link = $this->row('attachments', $id);
        if ((int) $link->course_version_id !== $versionId) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        return $this->open((int) $link->asset_id);
    }

    /** Remove old assets only after locking and proving they have no owning records. */
    public function purgeOrphaned(int $limit = 100): array
    {
        $limit = max(1, min(100, $limit));
        $retentionHours = max(24, min(8760, (int) $this->storage->config()->get('material_orphan_retention_hours', 24)));
        $q = $this->db->createQuery()->select('a.id, a.storage, a.storage_key')->from('#__jlms_assets AS a')
            ->where('a.created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ' . $retentionHours . ' HOUR)')
            ->where("a.storage = 'local'")
            ->where('NOT EXISTS (SELECT 1 FROM #__jlms_attachments x WHERE x.asset_id = a.id)')
            ->where('NOT EXISTS (SELECT 1 FROM #__jlms_lessons x WHERE x.media_asset_id = a.id)')
            ->where('NOT EXISTS (SELECT 1 FROM #__jlms_certificate_templates x WHERE x.background_asset_id = a.id)')
            ->where('NOT EXISTS (SELECT 1 FROM #__jlms_certificate_documents x WHERE x.pdf_asset_id = a.id)')
            ->where('NOT EXISTS (SELECT 1 FROM #__jlms_jobs x WHERE x.result_asset_id = a.id)')
            ->order('a.id ASC');
        $q->setQuery((string) $q . ' LIMIT ' . $limit . ' FOR UPDATE');
        $this->db->transactionStart();
        $deleted = 0;
        $failed = 0;
        try {
            $assets = $this->db->setQuery($q)->loadObjectList();
            foreach ($assets as $asset) {
                if (!$this->storage->remove($asset)) { $failed++; continue; }
                $delete = $this->db->createQuery()->delete('#__jlms_assets')->where('id = :id')
                    ->bind(':id', $asset->id, ParameterType::INTEGER);
                $this->db->setQuery($delete)->execute();
                $deleted++;
            }
            $this->db->transactionCommit();
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }
        return ['deleted' => $deleted, 'failed' => $failed, 'has_more' => count($assets) === $limit];
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
