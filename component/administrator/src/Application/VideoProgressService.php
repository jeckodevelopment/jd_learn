<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\Coverage;
use Jlms\Component\Jlms\Administrator\Domain\VideoTelemetry;
use Jlms\Contracts\Video\PlaybackContext;
use Jlms\Contracts\Video\VideoSource;

final class VideoProgressService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning,
        private readonly CurriculumService $curriculum, private readonly \Closure $clock, private readonly ?LocalVideoService $localVideos = null)
    {
    }

    private function transaction(\Closure $work): mixed
    {
        $this->db->transactionStart();
        try { $result = $work(); $this->db->transactionCommit(); return $result; }
        catch (\Throwable $error) { $this->db->transactionRollback(); throw $error; }
    }

    private static function date(int $ms): string
    {
        return gmdate('Y-m-d H:i:s', intdiv($ms, 1000)) . sprintf('.%03d000', $ms % 1000);
    }

    private function context(User $identity, string $language, int $enrollmentId, int $lessonId, bool $lock = true): array
    {
        $context = $this->learning->videoContext($identity, $language, $enrollmentId, $lessonId, $lock);
        $policy = ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked');
        if ($context[3]->provider_key !== 'html5' && !in_array($policy, ['embed', 'site_consent'], true)) { throw new \RuntimeException('COM_JLMS_VIDEO_CONSENT_REQUIRED', 403); }
        try { $provider = $this->curriculum->videoProvider($context[3]->provider_key); }
        catch (\DomainException $error) { throw new \RuntimeException('COM_JLMS_VIDEO_UNAVAILABLE', 503); }
        if (!$provider->getDescriptor()->configured) { throw new \RuntimeException('COM_JLMS_VIDEO_UNAVAILABLE', 503); }
        if ($context[3]->provider_key === 'html5') {
            if (!$this->localVideos) { throw new \RuntimeException('COM_JLMS_VIDEO_UNAVAILABLE', 503); }
            $this->localVideos->assertReady($context[3]);
        }
        return [...$context, $provider];
    }

    private function progress(int $enrollmentId, object $lesson): ?object
    {
        $lessonId = (int) $lesson->id;
        $q = $this->db->createQuery()->select('*')->from('#__jlms_lesson_progress')
            ->where('enrollment_id = :enrollment')->where('lesson_id = :lesson')
            ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':lesson', $lessonId, ParameterType::INTEGER);
        $q->setQuery((string) $q . ' FOR UPDATE');
        $row = $this->db->setQuery($q)->loadObject();
        if ($row && (int) $row->source_revision !== (int) $lesson->source_revision) {
            throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409);
        }
        return $row;
    }

    /** Remove a bounded batch of expired player sessions during an authorised playback start. */
    private function purgeExpiredSessions(int $now): void
    {
        $expired = $this->db->createQuery()->select($this->db->quoteName('id'))->from('#__jlms_progress_sessions')
            ->where($this->db->quoteName('expires_at') . ' <= ' . $this->db->quote(self::date($now)))
            ->order('expires_at, id')->setLimit(250);
        $delete = $this->db->createQuery()->delete($this->db->quoteName('#__jlms_progress_sessions'))
            ->where('id IN (SELECT id FROM (' . $expired . ') AS jlms_expired_sessions)');
        $this->db->setQuery($delete)->execute();
    }

    private static function uuid(): string
    {
        $hex = bin2hex(random_bytes(16));
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3) . '-a' . substr($hex, 17, 3) . '-' . substr($hex, 20);
    }

    private static function fingerprint(object $lesson): string
    {
        return hash('sha256', json_encode([$lesson->id, $lesson->course_version_id, $lesson->source_revision,
            $lesson->source_json, $lesson->access_expires_at], JSON_THROW_ON_ERROR));
    }

    private function descriptor(array $context): \Jlms\Contracts\Video\PlaybackDescriptor
    {
        [$actor, , $enrollment, $lesson, $provider] = $context;
        $source = json_decode($lesson->source_json, true, 16, JSON_THROW_ON_ERROR);
        $video = new VideoSource($lesson->provider_key, $source['reference'], (int) $lesson->source_revision, $source['options'] ?? []);
        $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $context[1]->rules_json);
        $trackingRequired = $rules['mode'] !== 'consultation';
        $capabilities = $provider->getCapabilities($video);
        if ($trackingRequired && !($capabilities->position && $capabilities->duration && $capabilities->seek)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_UNAVAILABLE', 503);
        }
        $expiry = $lesson->access_expires_at ? new \DateTimeImmutable($lesson->access_expires_at, new \DateTimeZone('UTC')) : null;
        // Remote work is always outside the write transaction. A second current-access check follows it.
        $descriptor = $provider->createPlaybackDescriptor($video, new PlaybackContext((int) $actor->id, (int) $enrollment->id,
            (int) $lesson->id, $context[5], 0, bin2hex(random_bytes(16)),
            new \DateTimeImmutable(self::date(($this->clock)()), new \DateTimeZone('UTC')), $expiry));
        $actual = $descriptor->capabilities;
        if ($trackingRequired && !($actual->position && $actual->duration && $actual->seek)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_UNAVAILABLE', 503);
        }
        if ($descriptor->expiresAt && $descriptor->expiresAt->getTimestamp() <= intdiv(($this->clock)(), 1000) + 5) {
            throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503);
        }
        return $descriptor;
    }

    private static function playbackState(array $state, \Jlms\Contracts\Video\PlaybackDescriptor $descriptor, int $now): array
    {
        if ($descriptor->expiresAt !== null) {
            if ($descriptor->expiresAt->getTimestamp() * 1000 <= $now + 5000) { throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503); }
            $state['playback_expiry'] = $descriptor->expiresAt->getTimestamp() * 1000;
            $state['playback_renew_at'] = $now + (int) $descriptor->playerOptions['renew_after_seconds'] * 1000;
            $state['playback_generation'] = ($state['playback_generation'] ?? 0) + 1;
        }
        return $state;
    }

    public function playback(User $identity, string $language, int $enrollmentId, int $lessonId, bool $consent): array
    {
        if (!$consent) { throw new \RuntimeException('COM_JLMS_VIDEO_CONSENT_REQUIRED', 403); }
        $preflight = $this->context($identity, $language, $enrollmentId, $lessonId, false);
        $fingerprint = self::fingerprint($preflight[3]);
        $descriptor = $this->descriptor([...$preflight, $language]);
        $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $preflight[1]->rules_json);
        if ($rules['mode'] === 'consultation') {
            $current = $this->context($identity, $language, $enrollmentId, $lessonId, false);
            if (!hash_equals($fingerprint, self::fingerprint($current[3]))) { throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409); }
            return ['tracking' => false, 'session_id' => null, 'source_revision' => (int) $current[3]->source_revision,
                'next_sequence' => null, 'resume_ms' => 0, 'duration_ms' => null, 'provider' => $descriptor->provider,
                'options' => $descriptor->playerOptions, 'expires_at' => $descriptor->expiresAt?->getTimestamp()];
        }
        return $this->transaction(function () use ($identity, $language, $enrollmentId, $lessonId, $fingerprint, $descriptor): array {
            [$actor, , $enrollment, $lesson, $provider] = $this->context($identity, $language, $enrollmentId, $lessonId);
            if (!hash_equals($fingerprint, self::fingerprint($lesson))) { throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409); }
            $now = ($this->clock)(); $date = self::date($now);
            $this->purgeExpiredSessions($now);
            $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_progress_sessions')
                ->where('enrollment_id = :enrollment')->where('lesson_id = :lesson')->where('expires_at > :now')
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':lesson', $lessonId, ParameterType::INTEGER)->bind(':now', $date);
            if ((int) $this->db->setQuery($q)->loadResult() >= 32) { throw new \RuntimeException('COM_JLMS_VIDEO_SESSION_LIMIT', 429); }
            $progress = $this->progress($enrollmentId, $lesson);
            if (!$progress) {
                $progress = (object) ['enrollment_id' => $enrollmentId, 'course_version_id' => (int) $lesson->course_version_id,
                    'lesson_id' => $lessonId, 'source_revision' => (int) $lesson->source_revision, 'coverage_json' => '[]',
                    'covered_ms' => 0, 'resume_ms' => 0, 'state' => 'started', 'last_activity_at' => $date, 'revision' => 1];
                $this->db->insertObject('#__jlms_lesson_progress', $progress);
            }
            $state = ['_schema' => 1, 'issued_ms' => $now, 'spent_wall_ms' => 0, 'last_event_id' => 0,
                'playing' => false, 'progress_revision' => (int) $progress->revision];
            $state = self::playbackState($state, $descriptor, $now);
            $session = (object) ['id' => self::uuid(), 'enrollment_id' => $enrollmentId, 'lesson_id' => $lessonId,
                'source_revision' => (int) $lesson->source_revision, 'sequence_no' => 0,
                'player_state_json' => json_encode($state, JSON_THROW_ON_ERROR), 'expires_at' => self::date($now + 7200000)];
            $this->db->insertObject('#__jlms_progress_sessions', $session);
            $update = (object) ['id' => $enrollmentId, 'started_at' => $enrollment->started_at ?: $date,
                'last_activity_at' => $date, 'last_lesson_id' => $lessonId, 'revision' => (int) $enrollment->revision + 1,
                'modified_at' => $date, 'modified_by' => (int) $actor->id];
            $this->db->updateObject('#__jlms_enrollments', $update, 'id');
            return ['tracking' => true, 'session_id' => $session->id, 'source_revision' => (int) $lesson->source_revision, 'next_sequence' => 1,
                'resume_ms' => (int) $progress->resume_ms, 'duration_ms' => (int) $lesson->duration_ms,
                'provider' => $descriptor->provider, 'options' => $descriptor->playerOptions,
                'expires_at' => $descriptor->expiresAt?->getTimestamp()];
        });
    }

    private function session(string $id, int $enrollmentId, int $lessonId, int $sourceRevision, int $now, bool $lock = true): object
    {
        if (!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D', $id)) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $q = $this->db->createQuery()->select('*')->from('#__jlms_progress_sessions')->where('id = :id')->bind(':id', $id);
        if ($lock) { $q->setQuery((string) $q . ' FOR UPDATE'); }
        $row = $this->db->setQuery($q)->loadObject();
        if (!$row || (int) $row->enrollment_id !== $enrollmentId || (int) $row->lesson_id !== $lessonId) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        if ((int) $row->source_revision !== $sourceRevision || $row->expires_at <= self::date($now)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409);
        }
        return $row;
    }

    private static function result(object $progress, object $enrollment, int $next, bool $replay, bool $conflict = false): array
    {
        return ['next_sequence' => $next, 'replay' => $replay, 'resume_conflict' => $conflict,
            'covered_ms' => (int) $progress->covered_ms, 'resume_ms' => (int) $progress->resume_ms,
            'lesson_completed' => $progress->state === 'completed', 'course_completed' => $enrollment->completed_at !== null,
            'course_percent' => (int) $enrollment->required_total > 0 ? min($enrollment->completed_at ? 100 : 99,
                (int) floor(100 * (int) $enrollment->completed_total / (int) $enrollment->required_total)) : 0];
    }

    public function save(User $identity, string $language, int $enrollmentId, int $lessonId, string $sessionId,
        int $sourceRevision, int $sequence, int $reportedDuration, string $eventsJson): array
    {
        if (strlen($eventsJson) > 65536) { throw new \RuntimeException('COM_JLMS_VIDEO_EVENTS_INVALID', 413); }
        if ($sequence < 1) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        try { $events = json_decode($eventsJson, true, 16, JSON_THROW_ON_ERROR); }
        catch (\JsonException $error) { throw new \RuntimeException('COM_JLMS_VIDEO_EVENTS_INVALID', 400); }
        if (!is_array($events)) { throw new \RuntimeException('COM_JLMS_VIDEO_EVENTS_INVALID', 400); }
        return $this->transaction(function () use ($identity, $language, $enrollmentId, $lessonId, $sessionId,
            $sourceRevision, $sequence, $reportedDuration, $events): array {
            [$actor, $version, $enrollment, $lesson] = $this->context($identity, $language, $enrollmentId, $lessonId);
            $now = ($this->clock)();
            if ((int) $lesson->source_revision !== $sourceRevision) { throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409); }
            if (!\Jlms\Component\Jlms\Administrator\Domain\VideoDuration::compatible((int)$lesson->duration_ms, $reportedDuration)) {
                throw new \RuntimeException('COM_JLMS_VIDEO_DURATION_MISMATCH', 422);
            }
            $progress = $this->progress($enrollmentId, $lesson);
            $session = $this->session($sessionId, $enrollmentId, $lessonId, $sourceRevision, $now);
            if (!$progress) { throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409); }
            $hash = hash('sha256', json_encode([$sourceRevision, $reportedDuration, $events], JSON_THROW_ON_ERROR));
            if ($sequence === (int) $session->sequence_no) {
                if (!hash_equals((string) $session->last_batch_hash, $hash)) { throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409); }
                $state = json_decode($session->player_state_json, true, 16, JSON_THROW_ON_ERROR);
                $resumeConflict = (int) $progress->revision !== (int) $state['progress_revision'];
                return self::result($progress, $enrollment, $sequence + 1, true, $resumeConflict);
            }
            if ($sequence !== (int) $session->sequence_no + 1) { throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409); }
            $state = json_decode($session->player_state_json, true, 16, JSON_THROW_ON_ERROR);
            if (isset($state['playback_expiry']) && $state['playback_expiry'] <= $now) { throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409); }
            try {
                $telemetry = VideoTelemetry::consume($state, $events, $reportedDuration, $now);
                $coverage = Coverage::merge(json_decode($progress->coverage_json, true, 32, JSON_THROW_ON_ERROR),
                    $telemetry['intervals'], min(\Jlms\Component\Jlms\Administrator\Domain\VideoDuration::MAX, (int)$lesson->duration_ms + \Jlms\Component\Jlms\Administrator\Domain\VideoDuration::tolerance((int)$lesson->duration_ms)));
            } catch (\InvalidArgumentException $error) { throw new \RuntimeException('COM_JLMS_VIDEO_EVENTS_INVALID', 422); }
            $covered = Coverage::coveredMs($coverage);
            $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode($version->rules_json);
            $lessonParams = json_decode((string)$lesson->params_json, true, 16, JSON_THROW_ON_ERROR);
            $consultation = $rules['mode'] === 'consultation';
            $threshold = $rules['video_threshold_basis_points'];
            if (array_key_exists('video_threshold_basis_points', $lessonParams)) {
                $override = $lessonParams['video_threshold_basis_points'];
                if (!is_int($override) || $override < 1 || $override > 10000) { throw new \RuntimeException('COM_JLMS_PUBLICATION_UNSUPPORTED', 422); }
                $threshold = $override;
            }
            $completed = !$consultation && Coverage::isComplete(min($covered, $reportedDuration), $reportedDuration, $threshold);
            $newCompletion = $completed && $progress->state !== 'completed';
            $resumeConflict = (int) $progress->revision !== $state['progress_revision'];
            $progress->coverage_json = json_encode($coverage, JSON_THROW_ON_ERROR);
            $progress->covered_ms = $covered;
            if (!$resumeConflict) { $progress->resume_ms = $telemetry['resume_ms']; }
            $progress->state = !$consultation && ($completed || $progress->state === 'completed') ? 'completed' : 'started';
            $progress->completed_at = $consultation ? null : ($progress->completed_at ?? ($completed ? self::date($now) : null));
            $progress->last_activity_at = self::date($now); $progress->revision = (int) $progress->revision + 1;
            $q = $this->db->createQuery()->update('#__jlms_lesson_progress')->set('coverage_json = :coverage')
                ->set('covered_ms = :covered')->set('resume_ms = :resume')->set('state = :state')->set($progress->completed_at === null ? 'completed_at = NULL' : 'completed_at = :completed')
                ->set('last_activity_at = :activity')->set('revision = :revision')->where('enrollment_id = :enrollment')->where('lesson_id = :lesson')
                ->bind(':coverage', $progress->coverage_json)->bind(':covered', $progress->covered_ms, ParameterType::INTEGER)
                ->bind(':resume', $progress->resume_ms, ParameterType::INTEGER)->bind(':state', $progress->state)
                ->bind(':activity', $progress->last_activity_at)->bind(':revision', $progress->revision, ParameterType::INTEGER)
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':lesson', $lessonId, ParameterType::INTEGER);
            if ($progress->completed_at !== null) { $q->bind(':completed', $progress->completed_at); }
            $this->db->setQuery($q)->execute();
            $total = (int) $enrollment->completed_total + ($newCompletion ? (int) $lesson->required : 0);
            if ($total >= (int) $enrollment->required_total && (int) $enrollment->required_total > 0) {
                $enrollment->completed_at = $enrollment->completed_at ?: self::date($now);
            }
            $enrollment->completed_total = $total;
            $update = (object) ['id' => $enrollmentId, 'completed_total' => $total, 'completed_at' => $enrollment->completed_at,
                'last_activity_at' => self::date($now), 'last_lesson_id' => $lessonId, 'revision' => (int) $enrollment->revision + 1,
                'modified_at' => self::date($now), 'modified_by' => (int) $actor->id];
            $this->db->updateObject('#__jlms_enrollments', $update, 'id', true);
            $telemetry['state']['progress_revision'] = $resumeConflict
                ? (int) $state['progress_revision']
                : (int) $progress->revision;
            $sessionUpdate = (object) ['id' => $sessionId, 'sequence_no' => $sequence, 'last_batch_hash' => $hash,
                'anchor_media_ms' => $telemetry['resume_ms'], 'anchor_at' => self::date($now),
                'player_state_json' => json_encode($telemetry['state'], JSON_THROW_ON_ERROR), 'expires_at' => self::date($now + 7200000)];
            $this->db->updateObject('#__jlms_progress_sessions', $sessionUpdate, 'id');
            return self::result($progress, $enrollment, $sequence + 1, false, $resumeConflict);
        });
    }

    public function renew(User $identity, string $language, int $enrollmentId, int $lessonId, string $sessionId,
        int $sourceRevision, bool $consent): array
    {
        if (!$consent) { throw new \RuntimeException('COM_JLMS_VIDEO_CONSENT_REQUIRED', 403); }
        $preflight = $this->context($identity, $language, $enrollmentId, $lessonId, false);
        $session = $this->session($sessionId, $enrollmentId, $lessonId, $sourceRevision, ($this->clock)(), false);
        $state = json_decode($session->player_state_json, true, 16, JSON_THROW_ON_ERROR);
        if ($preflight[3]->provider_key !== 'cloudflarestream' || (int) $preflight[3]->source_revision !== $sourceRevision
            || !isset($state['playback_renew_at']) || $state['playback_renew_at'] > ($this->clock)()) {
            throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409);
        }
        $fingerprint = self::fingerprint($preflight[3]); $generation = $state['playback_generation'];
        $descriptor = $this->descriptor([...$preflight, $language]);
        return $this->transaction(function () use ($identity, $language, $enrollmentId, $lessonId, $sessionId,
            $sourceRevision, $fingerprint, $generation, $descriptor): array {
            [, , , $lesson] = $this->context($identity, $language, $enrollmentId, $lessonId);
            $now = ($this->clock)();
            $session = $this->session($sessionId, $enrollmentId, $lessonId, $sourceRevision, $now);
            $state = json_decode($session->player_state_json, true, 16, JSON_THROW_ON_ERROR);
            if (!hash_equals($fingerprint, self::fingerprint($lesson)) || $state['playback_generation'] !== $generation) {
                throw new \RuntimeException('COM_JLMS_VIDEO_CONFLICT', 409);
            }
            $state = self::playbackState($state, $descriptor, $now);
            // Recreating an iframe on renewal must break the continuity anchor, never join a seek gap.
            $state['playing'] = false;
            $update = (object) ['id' => $sessionId, 'player_state_json' => json_encode($state, JSON_THROW_ON_ERROR),
                'expires_at' => self::date($now + 7200000)];
            $this->db->updateObject('#__jlms_progress_sessions', $update, 'id');
            return ['provider' => $descriptor->provider, 'options' => $descriptor->playerOptions,
                'expires_at' => $descriptor->expiresAt?->getTimestamp(), 'source_revision' => $sourceRevision];
        });
    }

    public function close(User $identity, string $language, int $enrollmentId, int $lessonId, string $sessionId): void
    {
        $this->transaction(function () use ($identity, $language, $enrollmentId, $lessonId, $sessionId): void {
            [, , , $lesson] = $this->context($identity, $language, $enrollmentId, $lessonId);
            $now = ($this->clock)();
            $session = $this->session($sessionId, $enrollmentId, $lessonId, (int) $lesson->source_revision, $now);
            $update = (object) ['id' => $session->id, 'expires_at' => self::date($now)];
            $this->db->updateObject('#__jlms_progress_sessions', $update, 'id');
        });
    }
}
