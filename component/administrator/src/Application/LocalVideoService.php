<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedVideoStorage;

/** A video is attached directly to a lesson; no unscoped asset IDs can be assigned by authors. */
final class LocalVideoService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning,
        private readonly ProtectedVideoStorage $storage, private readonly ?\Closure $enabled = null)
    {
    }

    private function row(string $table, int $id, bool $lock = false): object
    {
        $q = $this->db->createQuery()->select('*')->from('#__jlms_' . $table)->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
        if ($lock) { $q->setQuery((string) $q . ' FOR UPDATE'); }
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
    }

    private function author(User $identity, int $course, int $version, int $lesson, bool $lock = false): array
    {
        $actor = $this->learning->freshActor($identity); $courseRow = $this->row('courses', $course, $lock);
        if (!$actor->authorise('core.manage', 'com_jlms') || !$actor->authorise('core.edit', 'com_jlms.course.' . $course)
            || !$actor->authorise('jlms.materials.manage', 'com_jlms.course.' . $course)) { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        $versionRow = $this->row('course_versions', $version, $lock); $lessonRow = $this->row('lessons', $lesson, $lock);
        if ((int) $versionRow->course_id !== $course || (int) $lessonRow->course_version_id !== $version
            || $lessonRow->kind !== 'video' || $lessonRow->provider_key !== 'html5') { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        if ($courseRow->checked_out && (int) $courseRow->checked_out !== (int) $actor->id) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
        return [$actor, $versionRow, $lessonRow];
    }

    private function transaction(\Closure $work): mixed
    {
        $this->db->transactionStart();
        try { $result = $work(); $this->db->transactionCommit(); return $result; }
        catch (\Throwable $error) { $this->db->transactionRollback(); throw $error; }
    }

    private static function draft(object $version, int $expected): void
    {
        if ($version->status !== 'draft') { throw new \RuntimeException('COM_JLMS_VERSION_IMMUTABLE', 409); }
        if ((int) $version->revision !== $expected) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
    }

    public function upload(User $identity, int $course, int $version, int $lesson, int $expected, array $upload): void
    {
        [, $v] = $this->author($identity, $course, $version, $lesson); self::draft($v, $expected);
        if (in_array($upload['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) { throw new \RuntimeException('COM_JLMS_FILE_SIZE', 413); }
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($upload['tmp_name'] ?? null)
            || !is_string($upload['name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) { throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422); }
        $staged = $this->storage->stage($upload['tmp_name'], $upload['name']);
        $scan = $this->storage->scan((object) $staged);
        $this->transaction(function () use ($identity, $course, $version, $lesson, $expected, $staged, $scan): void {
            [$actor, $v, $l] = $this->author($identity, $course, $version, $lesson, true); self::draft($v, $expected);
            $now = gmdate('Y-m-d H:i:s');
            $metadata = array_intersect_key($staged, array_flip(['duration_ms', 'video_codec', 'audio_codec', 'sha256']));
            $fields = array_diff_key($staged, array_flip(['duration_ms', 'video_codec', 'audio_codec']));
            $asset = (object) ($fields + $scan + ['asset_revision' => 1, 'created_by' => (int) $actor->id, 'modified_by' => (int) $actor->id,
                'created_at' => $now, 'modified_at' => $now]);
            $this->db->insertObject('#__jlms_assets', $asset, 'id');
            $update = (object) ['id' => $lesson, 'media_asset_id' => (int) $asset->id, 'duration_ms' => $staged['duration_ms'],
                'source_json' => json_encode(['_schema' => 1, 'provider' => 'html5', 'reference' => (string) $asset->id, 'asset_sha256' => $asset->sha256, 'options' => []], JSON_THROW_ON_ERROR),
                'params_json' => json_encode(['video_duration_confirmed' => true, 'local_video' => $metadata], JSON_THROW_ON_ERROR),
                'source_revision' => (int) $l->source_revision + 1, 'revision' => (int) $l->revision + 1,
                'modified_by' => (int) $actor->id, 'modified_at' => $now];
            $this->db->updateObject('#__jlms_lessons', $update, 'id');
            $touch = (object) ['id' => $version, 'revision' => (int) $v->revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => $now];
            $this->db->updateObject('#__jlms_course_versions', $touch, 'id');
        });
    }

    public function scan(User $identity, int $course, int $version, int $lesson, int $expected): void
    {
        [, $v, $l] = $this->author($identity, $course, $version, $lesson);
        if ((int) $v->revision !== $expected) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
        $id = (int) $l->media_asset_id; $scan = $this->storage->scan($this->row('assets', $id));
        $this->transaction(function () use ($identity, $course, $version, $lesson, $expected, $id, $scan): void {
            [$actor, $v, $l] = $this->author($identity, $course, $version, $lesson, true);
            if ((int) $v->revision !== $expected || (int) $l->media_asset_id !== $id) { throw new \RuntimeException('COM_JLMS_REVISION_CONFLICT', 409); }
            $update = (object) (['id' => $id] + $scan + ['modified_by' => (int) $actor->id, 'modified_at' => gmdate('Y-m-d H:i:s')]);
            $this->db->updateObject('#__jlms_assets', $update, 'id');
        });
    }

    public function assertReady(object $lesson): object
    {
        if (!$this->enabled || !($this->enabled)()) { throw new \RuntimeException('COM_JLMS_VIDEO_UNAVAILABLE', 503); }
        if ($lesson->provider_key !== 'html5' || !$lesson->media_asset_id || !$lesson->source_json) { throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422); }
        $asset = $this->row('assets', (int) $lesson->media_asset_id);
        $source = json_decode($lesson->source_json, true, 16, JSON_THROW_ON_ERROR);
        $params = json_decode($lesson->params_json, true, 16, JSON_THROW_ON_ERROR);
        $metadata = $params['local_video'] ?? [];
        if ($asset->storage !== 'local_video' || $asset->mime_type !== 'video/mp4' || $asset->status !== 'ready'
            || ($source['reference'] ?? '') !== (string) $asset->id || ($source['asset_sha256'] ?? '') !== $asset->sha256
            || ($metadata['sha256'] ?? '') !== $asset->sha256 || ($metadata['duration_ms'] ?? 0) !== (int) $lesson->duration_ms
            || ($metadata['video_codec'] ?? '') !== 'h264') { throw new \RuntimeException('COM_JLMS_MATERIAL_NOT_READY', 503); }
        if ((bool) $this->storage->config()->get('material_scan_required', false)) {
            $scan = json_decode($asset->scan_json ?? '{}', true);
            if (($scan['engine'] ?? '') !== 'clamd' || ($scan['result'] ?? '') !== 'clean') { throw new \RuntimeException('COM_JLMS_MATERIAL_NOT_READY', 503); }
        }
        $this->storage->delivery($asset);
        return $asset;
    }

    public function delivery(User $identity, string $language, int $enrollment, int $lesson, int $sourceRevision): array
    {
        [, , , $l] = $this->learning->videoContext($identity, $language, $enrollment, $lesson);
        if ($l->provider_key !== 'html5' || (int) $l->source_revision !== $sourceRevision) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        $asset = $this->assertReady($l); $delivery = $this->storage->delivery($asset);
        if ($delivery['mode'] === 'php_bounded') {
            $handle = @fopen($delivery['path'], 'rb'); $stat = $handle ? fstat($handle) : false;
            if (!$stat || $stat['size'] !== (int) $asset->byte_size || ($stat['mode'] & 0170000) !== 0100000) {
                if (is_resource($handle)) { fclose($handle); } throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
            $delivery['handle'] = $handle;
        }
        unset($delivery['path']);
        return $delivery + ['size' => (int) $asset->byte_size, 'name' => 'video.mp4', 'mime' => 'video/mp4', 'etag' => '"' . $asset->sha256 . '"', 'inline' => true];
    }
}
