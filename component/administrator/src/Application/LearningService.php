<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\LessonAvailability;

/** Text learning only. All protected operations refresh core identity and access, including replays. */
final class LearningService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly CatalogService $catalog)
    {
    }

    public function freshActor(User $identity): User
    {
        if (!(int) $identity->id || $identity->guest) {
            throw new \RuntimeException('COM_JLMS_LOGIN_REQUIRED', 401);
        }
        $actor = new User((int) $identity->id);
        if (!(int) $actor->id || $actor->block) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        $actor->clearAccessRights();
        return $actor;
    }

    private function row(string $table, int $id, bool $lock = false): object
    {
        $q = $this->db->createQuery()->select('*')->from($this->db->quoteName('#__jlms_' . $table))
            ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
        if ($lock) {
            $q->setQuery((string) $q . ' FOR UPDATE');
        }
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
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

    private static function window(object $record): void
    {
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        if (($record->starts_at !== null && $record->starts_at > $now)
            || ($record->ends_at !== null && $record->ends_at <= $now)) {
            throw new \RuntimeException('COM_JLMS_LEARNING_WINDOW', 403);
        }
    }

    /** Reject an admission whose required lessons cannot all fit before the known closing windows. */
    public function assertEnrollmentScheduleReachable(int $courseId, int $versionId, int $editionId,
        ?string $enrollmentStartsAt = null, ?string $enrollmentEndsAt = null, ?string $startedAt = null): void
    {
        $course = $this->row('courses', $courseId);
        $edition = $this->row('editions', $editionId);
        if ((int) $edition->course_id !== $courseId || (int) $edition->course_version_id !== $versionId) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        $window = CourseSchedule::read($course);
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $base = $startedAt ? new \DateTimeImmutable($startedAt, new \DateTimeZone('UTC')) : $now;
        foreach ([$window['publish_up'], $window['starts_at'], $edition->starts_at, $enrollmentStartsAt] as $candidate) {
            if ($candidate) {
                $date = new \DateTimeImmutable($candidate, new \DateTimeZone('UTC'));
                if ($date > $base) { $base = $date; }
            }
        }
        $q = $this->db->createQuery()->select('publish_up,publish_down,release_after_days')->from('#__jlms_lessons')
            ->where('course_version_id = :version')->where($this->db->quoteName('required') . ' = 1')
            ->bind(':version', $versionId, ParameterType::INTEGER)->order('ordering,id');
        foreach ($this->db->setQuery($q, 0, 5000)->loadObjectList() as $lesson) {
            $days = LessonAvailability::normalizeReleaseDays($lesson->release_after_days);
            if (!LessonAvailability::validStored($lesson->publish_up, $lesson->publish_down)) {
                throw new \RuntimeException('COM_JLMS_LESSON_SCHEDULE_INVALID', 409);
            }
            $available = LessonAvailability::effectiveStart($lesson->publish_up, $days, $days ? $base->format('Y-m-d H:i:s') : null);
            foreach ([$window['publish_up'], $window['starts_at']] as $courseStart) {
                if ($courseStart) {
                    $date = new \DateTimeImmutable($courseStart, new \DateTimeZone('UTC'));
                    if (!$available || $date > $available) { $available = $date; }
                }
            }
            $close = null;
            foreach ([$lesson->publish_down, $window['publish_down'], $window['ends_at'], $edition->ends_at, $enrollmentEndsAt] as $candidate) {
                if ($candidate) {
                    $date = new \DateTimeImmutable($candidate, new \DateTimeZone('UTC'));
                    if (!$close || $date < $close) { $close = $date; }
                }
            }
            if ($available && $close && $available >= $close) {
                throw new \RuntimeException('COM_JLMS_LESSON_UNREACHABLE', 409);
            }
        }
    }

    private function course(User $actor, string $language, int $id): object
    {
        $course = $this->catalog->course($actor, $language, $id);
        if (!in_array((int) $course->content_access, array_map('intval', $actor->getAuthorisedViewLevels()), true)) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        return $course;
    }

    private function enrolledCourse(User $actor, string $language, int $id): object
    {
        $course = $this->catalog->enrolledCourse($actor, $language, $id);
        if (!in_array((int) $course->content_access, array_map('intval', $actor->getAuthorisedViewLevels()), true)) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        if (!(int) $course->delivery_enabled) { throw new \RuntimeException('COM_JLMS_COURSE_DELIVERY_SUSPENDED', 403); }
        return $course;
    }

    private function authorisedEnrollment(User $actor, string $language, int $id, bool $lock = false, bool $allowExpiredWindow = false): array
    {
        $enrollment = $this->row('enrollments', $id, $lock);
        if ((int) $enrollment->user_id !== (int) $actor->id) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $course = $this->enrolledCourse($actor, $language, (int) $enrollment->course_id);
        $version = $this->row('course_versions', (int) $enrollment->course_version_id);
        $edition = $this->row('editions', (int) $enrollment->edition_id);
        if (!in_array($enrollment->status, ['active', 'completed'], true)
            || $version->status !== 'published' || (int) $version->course_id !== (int) $course->id
            || (int) $edition->course_id !== (int) $course->id
            || (int) $edition->course_version_id !== (int) $version->id || $edition->status !== 'open') {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        $rules = json_decode($version->rules_json, true, 32, JSON_THROW_ON_ERROR);
        if (($rules['mode'] ?? '') !== 'learning' || !in_array(($rules['sequence_mode'] ?? ''), ['free', 'sequential'], true)
            || ($rules['certificate_enabled'] ?? true) !== false || ($rules['completion_policy'] ?? '') !== 'all_required') {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        if (!$allowExpiredWindow) { self::window($edition); self::window($enrollment); }
        else {
            $now = gmdate('Y-m-d H:i:s');
            if (($edition->starts_at !== null && $edition->starts_at > $now) || ($enrollment->starts_at !== null && $enrollment->starts_at > $now)) {
                throw new \RuntimeException('COM_JLMS_LEARNING_WINDOW', 403);
            }
        }
        return [$course, $version, $edition, $enrollment];
    }

    private static function key(string $key): void
    {
        if (preg_match('/\A[a-zA-Z0-9-]{32,64}\z/D', $key) !== 1) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
    }

    private function ledger(User $actor, string $action, string $key, array $payload, int $enrollmentId): void
    {
        self::key($key);
        $scope = hash('sha256', $actor->id . ':' . $action . ':' . $key);
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $now = gmdate('Y-m-d H:i:s');
        $expires = gmdate('Y-m-d H:i:s', time() + 86400);
        // Unique-key upsert avoids a missing-row gap-lock race across different payloads using the same key.
        $q = $this->db->createQuery();
        $q->setQuery('INSERT INTO ' . $this->db->quoteName('#__jlms_idempotency')
            . " (scope_hash, request_hash, state, result_type, result_id, http_status, created_at, expires_at)"
            . " VALUES (:scope, :hash, 'completed', 'enrollment', :result, 200, :now, :expires)"
            . ' ON DUPLICATE KEY UPDATE scope_hash = scope_hash')
            ->bind(':scope', $scope)->bind(':hash', $hash)->bind(':result', $enrollmentId, ParameterType::INTEGER)
            ->bind(':now', $now)->bind(':expires', $expires);
        $this->db->setQuery($q)->execute();
        $q = $this->db->createQuery()->select('request_hash, result_id')->from('#__jlms_idempotency')
            ->where('scope_hash = :scope')->bind(':scope', $scope);
        $q->setQuery((string) $q . ' FOR UPDATE');
        $existing = $this->db->setQuery($q)->loadObject();
        if (!$existing || !hash_equals($existing->request_hash, $hash) || (int) $existing->result_id !== $enrollmentId) {
            throw new \RuntimeException('COM_JLMS_IDEMPOTENCY_CONFLICT', 409);
        }
    }

    private function subject(User $actor): string
    {
        $hex = bin2hex(random_bytes(16));
        $key = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3)
            . '-a' . substr($hex, 17, 3) . '-' . substr($hex, 20);
        $id = (int) $actor->id;
        $now = gmdate('Y-m-d H:i:s');
        $q = $this->db->createQuery();
        $q->setQuery('INSERT INTO ' . $this->db->quoteName('#__jlms_subjects')
            . ' (subject_key, user_id, created_at) VALUES (:subject, :user, :now) ON DUPLICATE KEY UPDATE user_id = user_id')
            ->bind(':subject', $key)->bind(':user', $id, ParameterType::INTEGER)->bind(':now', $now);
        $this->db->setQuery($q)->execute();
        $q = $this->db->createQuery()->select('subject_key')->from('#__jlms_subjects')
            ->where('user_id = :user')->bind(':user', $id, ParameterType::INTEGER);
        return (string) $this->db->setQuery($q)->loadResult();
    }

    public function start(User $identity, string $language, int $courseId, string $key): int
    {
        self::key($key);
        $actor = $this->freshActor($identity);
        $course = $this->course($actor, $language, $courseId);
        $fullCourse = $this->row('courses', $courseId);
        if ($fullCourse->enrollment_mode !== 'auto') {
            throw new \RuntimeException('COM_JLMS_ENROLLMENT_MODE_PENDING', 409);
        }
        // Upsert before didactic transaction; unique user_id serialises identity resolution only.
        $subject = $this->subject($actor);
        // Resolve the candidate outside the transaction. A plain SELECT inside it
        // would establish a REPEATABLE READ view before waiting for the edition
        // lock, hiding admissions committed by the previous lock owner.
        $versionId = (int) $course->current_version_id;
        $q = $this->db->createQuery()->select('id')->from('#__jlms_editions')->where('course_id = :course')
            ->where('course_version_id = :version')->where('code = :code')
            ->bind(':course', $courseId, ParameterType::INTEGER)->bind(':version', $versionId, ParameterType::INTEGER);
        $code = 'default-v' . (int) $this->row('course_versions', $versionId)->version_no;
        $q->bind(':code', $code);
        $editionId = (int) $this->db->setQuery($q)->loadResult();
        return $this->transaction(function () use ($actor, $language, $courseId, $versionId, $editionId, $subject, $key): int {
            // First transactional read: same serialization point as manual admission.
            $edition = $this->row('editions', $editionId, true);
            if ((int) $edition->course_id !== $courseId || (int) $edition->course_version_id !== $versionId) {
                throw new \RuntimeException('COM_JLMS_IDEMPOTENCY_CONFLICT', 409);
            }
            // Refresh core identity and availability after waiting, before any mutation.
            $actor = $this->freshActor($actor);
            $this->course($actor, $language, $courseId);
            $current = $this->row('courses', $courseId);
            if ((int) $current->current_version_id !== $versionId || $current->enrollment_mode !== 'auto') {
                throw new \RuntimeException('COM_JLMS_IDEMPOTENCY_CONFLICT', 409);
            }
            if ($edition->status !== 'open') {
                throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
            }
            self::window($edition);
            $editionId = (int) $edition->id;
            $q = $this->db->createQuery()->select('id')->from('#__jlms_enrollments')->where('subject_key = :subject')
                ->where('edition_id = :edition')->bind(':subject', $subject)->bind(':edition', $editionId, ParameterType::INTEGER);
            $id = (int) $this->db->setQuery($q)->loadResult();
            if ($id) {
                $this->authorisedEnrollment($actor, $language, $id, true);
            } else {
                if (!(int) $current->admissions_open) { throw new \RuntimeException('COM_JLMS_COURSE_ADMISSIONS_CLOSED', 409); }
                if ($edition->capacity !== null) {
                    $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_enrollments')->where('edition_id = :edition')
                        ->where("status <> 'cancelled'")->bind(':edition', $editionId, ParameterType::INTEGER);
                    if ((int) $this->db->setQuery($q)->loadResult() >= (int) $edition->capacity) {
                        throw new \RuntimeException('COM_JLMS_EDITION_FULL', 409);
                    }
                }
                $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_lessons')->where('course_version_id = :version')
                    ->where($this->db->quoteName('required') . ' = 1')->bind(':version', $versionId, ParameterType::INTEGER);
                $total = (int) $this->db->setQuery($q)->loadResult();
                if ($total < 1) {
                    throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
                }
                $this->assertEnrollmentScheduleReachable($courseId, $versionId, $editionId,
                    $edition->starts_at, $edition->ends_at);
                $now = gmdate('Y-m-d H:i:s');
                $record = (object) ['user_id' => (int) $actor->id, 'subject_key' => $subject, 'course_id' => $courseId,
                    'course_version_id' => $versionId, 'edition_id' => $editionId, 'status' => 'active', 'origin' => 'start',
                    'required_total' => $total, 'completed_total' => 0, 'revision' => 1, 'created_by' => (int) $actor->id,
                    'modified_by' => (int) $actor->id, 'created_at' => $now, 'modified_at' => $now];
                $this->db->insertObject('#__jlms_enrollments', $record, 'id');
                $id = (int) $record->id;
            }
            $this->ledger($actor, 'enrollment.start', $key, ['course_id' => $courseId], $id);
            return $id;
        });
    }

    private function lesson(int $versionId, int $lessonId, bool $includeBody = false): object
    {
        $columns = ['id', 'course_version_id', 'title', 'kind', 'required', 'completion_rule',
            'publish_up', 'publish_down', 'release_after_days', 'source_revision', 'provider_key', 'duration_ms', 'media_asset_id', 'params_json'];
        if ($includeBody) {
            $columns[] = 'body';
            $columns[] = 'fields_snapshot_json';
        }
        $q = $this->db->createQuery()->select($this->db->quoteName($columns))->from('#__jlms_lessons')
            ->where('id = :lesson')->where('course_version_id = :version')
            ->bind(':lesson', $lessonId, ParameterType::INTEGER)->bind(':version', $versionId, ParameterType::INTEGER);
        $lesson = $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        if ((int) $lesson->course_version_id !== $versionId) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $supported = ($lesson->kind === 'quiz' && $lesson->completion_rule === 'quiz_pass')
            || (in_array($lesson->kind, ['text', 'document', 'audio'], true) && $lesson->completion_rule === 'manual')
            || ($lesson->kind === 'video' && $lesson->completion_rule === 'video_coverage'
                && is_string($lesson->provider_key) && preg_match('/\A[a-z][a-z0-9_]{1,63}\z/D', $lesson->provider_key) === 1
                && (int) $lesson->duration_ms >= 1000);
        if (!$supported || !LessonAvailability::validReleaseDays($lesson->release_after_days)) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        return $lesson;
    }

    private function assertLessonAvailable(object $lesson, object $enrollment): void
    {
        $state = LessonAvailability::state($lesson->publish_up, $lesson->publish_down,
            $lesson->release_after_days, $enrollment->started_at);
        if ($state === 'upcoming' || $state === 'awaiting_start') {
            throw new \RuntimeException('COM_JLMS_LESSON_NOT_YET_AVAILABLE', 403);
        }
        if ($state === 'closed') {
            throw new \RuntimeException('COM_JLMS_LESSON_CLOSED', 403);
        }
    }

    /** Every learner-facing route uses the same server-side prerequisite decision. */
    private function prerequisiteBlocker(int $enrollmentId, int $lessonId, int $versionId, bool $sequential): ?object
    {
        $q = $this->db->createQuery()->select('required.id, required.title')->from('#__jlms_prerequisites AS r')
            ->innerJoin('#__jlms_lessons AS required ON required.id = r.required_lesson_id AND required.course_version_id = r.course_version_id')
            ->leftJoin('#__jlms_lesson_progress AS p ON p.lesson_id = r.required_lesson_id AND p.enrollment_id = :enrollment')
            ->where('r.lesson_id = :lesson')->where("(p.state IS NULL OR p.state <> 'completed')")
            ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':lesson', $lessonId, ParameterType::INTEGER);
        $explicit = $this->db->setQuery($q, 0, 1)->loadObject();
        if ($explicit) { return $explicit; }
        if (!$sequential) { return null; }
        $sequence = $this->db->createQuery()->select('required.id, required.title')
            ->from('#__jlms_lessons AS target')->innerJoin('#__jlms_modules AS targetModule ON targetModule.id = target.module_id AND targetModule.course_version_id = target.course_version_id')
            ->innerJoin('#__jlms_lessons AS required ON required.course_version_id = target.course_version_id AND required.required = 1')
            ->innerJoin('#__jlms_modules AS requiredModule ON requiredModule.id = required.module_id AND requiredModule.course_version_id = required.course_version_id')
            ->leftJoin('#__jlms_lesson_progress AS p ON p.lesson_id = required.id AND p.enrollment_id = :sequenceEnrollment')
            ->where('target.id = :sequenceTarget')->where('target.course_version_id = :sequenceVersion')
            ->where("(requiredModule.ordering < targetModule.ordering OR (requiredModule.ordering = targetModule.ordering AND (requiredModule.id < targetModule.id OR (requiredModule.id = targetModule.id AND (required.ordering < target.ordering OR (required.ordering = target.ordering AND required.id < target.id))))))")
            ->where("(p.state IS NULL OR p.state <> 'completed')")
            ->bind(':sequenceEnrollment', $enrollmentId, ParameterType::INTEGER)
            ->bind(':sequenceTarget', $lessonId, ParameterType::INTEGER)->bind(':sequenceVersion', $versionId, ParameterType::INTEGER)
            ->order('requiredModule.ordering DESC, requiredModule.id DESC, required.ordering DESC, required.id DESC');
        return $this->db->setQuery($sequence, 0, 1)->loadObject() ?: null;
    }

    private function assertPrerequisitesComplete(int $enrollmentId, int $lessonId, object $version): void
    {
        $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $version->rules_json);
        if ($this->prerequisiteBlocker($enrollmentId, $lessonId, (int) $version->id, $rules['sequence_mode'] === 'sequential')) {
            throw new \RuntimeException('COM_JLMS_PREREQUISITES_INCOMPLETE', 403);
        }
    }

    public function quizContext(User $identity, string $language, int $enrollmentId, int $lessonId, bool $lock = false, bool $allowExpiredWindow = false): array
    {
        $actor = $this->freshActor($identity);
        [, $version, , $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId, $lock, $allowExpiredWindow);
        $lesson = $this->lesson((int) $version->id, $lessonId);
        $this->assertLessonAvailable($lesson, $enrollment);
        $this->assertPrerequisitesComplete($enrollmentId, $lessonId, $version);
        if ($lesson->kind !== 'quiz') { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        return [$actor, $enrollment, $lesson];
    }

    /** Internal video service context; controllers never serialise these database records. */
    public function videoContext(User $identity, string $language, int $enrollmentId, int $lessonId, bool $lock = false): array
    {
        $actor = $this->freshActor($identity);
        [$course, $version, $edition, $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId, $lock);
        $lesson = $this->lesson((int) $version->id, $lessonId);
        $this->assertLessonAvailable($lesson, $enrollment);
        $this->assertPrerequisitesComplete($enrollmentId, $lessonId, $version);
        if ($lesson->kind !== 'video') { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        $courseId = (int) $course->id;
        $q = $this->db->createQuery()->select('l.source_json')->from('#__jlms_lessons AS l')
            ->where('l.id = :lesson')->bind(':lesson', $lessonId, ParameterType::INTEGER);
        $source = $this->db->setQuery($q)->loadObject();
        $lesson->source_json = $source->source_json;
        $deadlines = array_filter([$edition->ends_at, $enrollment->ends_at], static fn ($v): bool => $v !== null && $v !== '');
        sort($deadlines);
        $lesson->access_expires_at = $deadlines[0] ?? null;
        return [$actor, $version, $enrollment, $lesson];
    }

    public function audioLessonContext(User $identity, string $language, int $enrollmentId, int $lessonId): array
    {
        $actor = $this->freshActor($identity); [$course, $version, , $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId);
        $lesson = $this->lesson((int) $version->id, $lessonId);
        $this->assertLessonAvailable($lesson, $enrollment);
        $this->assertPrerequisitesComplete($enrollmentId, $lessonId, $version);
        if ($lesson->kind !== 'audio') { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
        return ['course_id' => (int) $course->id, 'version_id' => (int) $version->id];
    }

    /** Reused by attachment policy: no lesson body, progress read or credit is produced. */
    public function resourceContext(User $identity, string $language, int $enrollmentId, int $lessonId = 0): array
    {
        $actor = $this->freshActor($identity);
        [$course, $version, , $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId);
        if ($lessonId) {
            $lesson = $this->lesson((int) $version->id, $lessonId);
            $this->assertLessonAvailable($lesson, $enrollment);
            $this->assertPrerequisitesComplete($enrollmentId, $lessonId, $version);
        }
        return ['course_id' => (int) $course->id, 'version_id' => (int) $version->id];
    }

    public function classroom(User $identity, string $language, int $enrollmentId, int $lessonId = 0): array
    {
        $actor = $this->freshActor($identity);
        [$course, $version, $edition, $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId);
        $versionId = (int) $version->id;
        $q = $this->db->createQuery()->select('m.id AS module_id, m.title AS module_title, l.id, l.title, l.required, l.publish_up, l.publish_down, l.release_after_days, p.state')
            ->from('#__jlms_modules AS m')->innerJoin('#__jlms_lessons AS l ON l.module_id = m.id AND l.course_version_id = m.course_version_id')
            ->leftJoin('#__jlms_lesson_progress AS p ON p.lesson_id = l.id AND p.enrollment_id = :enrollment')
            ->where('m.course_version_id = :version')->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
            ->bind(':version', $versionId, ParameterType::INTEGER)->order('m.ordering, m.id, l.ordering, l.id');
        $outline = $this->db->setQuery($q, 0, 5000)->loadObjectList();
        if (!$outline) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $blockers = [];
        $blockerQuery = $this->db->createQuery()->select('r.lesson_id, required.id AS required_id, required.title AS required_title')
            ->from('#__jlms_prerequisites AS r')->innerJoin('#__jlms_lessons AS required ON required.id = r.required_lesson_id AND required.course_version_id = r.course_version_id')
            ->leftJoin('#__jlms_lesson_progress AS p ON p.lesson_id = r.required_lesson_id AND p.enrollment_id = :blockerEnrollment')
            ->where('r.course_version_id = :blockerVersion')->where("(p.state IS NULL OR p.state <> 'completed')")
            ->bind(':blockerEnrollment', $enrollmentId, ParameterType::INTEGER)->bind(':blockerVersion', $versionId, ParameterType::INTEGER)
            ->order('r.lesson_id, required.ordering, required.id');
        foreach ($this->db->setQuery($blockerQuery, 0, 25001)->loadObjectList() as $blocker) {
            $blockers[(int) $blocker->lesson_id] ??= $blocker;
        }
        $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $version->rules_json);
        $timezone = CourseSchedule::read($course)['timezone'];
        if ($rules['sequence_mode'] === 'sequential') {
            $pendingRequired = [];
            foreach ($outline as $entry) {
                if ($pendingRequired && !isset($blockers[(int) $entry->id])) {
                    $blockers[(int) $entry->id] = (object) ['required_id' => (int) $pendingRequired[array_key_last($pendingRequired)]->id,
                        'required_title' => (string) $pendingRequired[array_key_last($pendingRequired)]->title];
                }
                if ((int) $entry->required === 1 && $entry->state !== 'completed') { $pendingRequired[] = $entry; }
            }
        }
        foreach ($outline as $entry) {
            $blocker = $blockers[(int) $entry->id] ?? null;
            $entry->prerequisite_blocked = $blocker !== null;
            $entry->blocked_by_id = $blocker ? (int) $blocker->required_id : 0;
            $entry->blocked_by_title = $blocker ? (string) $blocker->required_title : '';
            $entry->schedule_state = LessonAvailability::state($entry->publish_up, $entry->publish_down, $entry->release_after_days, $enrollment->started_at);
            $entry->schedule_blocked = $entry->schedule_state !== 'available';
            $entry->schedule_available_at = $entry->schedule_state === 'upcoming'
                ? LessonAvailability::displayEffectiveStart($entry->publish_up, $entry->release_after_days, $enrollment->started_at, $timezone) : '';
            $entry->schedule_after_days = $entry->schedule_state === 'awaiting_start' ? (int) $entry->release_after_days : 0;
        }
        $lessonId = $lessonId ?: (int) ($enrollment->last_lesson_id ?: $outline[0]->id);
        $blocker = $blockers[$lessonId] ?? null;
        $selectedScheduleState = 'available';
        foreach ($outline as $entry) {
            if ((int) $entry->id === $lessonId) { $selectedScheduleState = $entry->schedule_state; break; }
        }
        $scheduleBlocked = $selectedScheduleState !== 'available';
        $lesson = $this->lesson($versionId, $lessonId, !$blocker && !$scheduleBlocked);
        $fieldSnapshot = json_decode((string) ($lesson->fields_snapshot_json ?? '{}'), true, 32, JSON_THROW_ON_ERROR);
        $viewLevels = array_map('intval', $actor->getAuthorisedViewLevels());
        $customFields = [];
        foreach (($fieldSnapshot['fields'] ?? []) as $name => $field) {
            if (!is_array($field) || !in_array((int) ($field['access'] ?? 0), $viewLevels, true)) { continue; }
            $customFields[] = ['name' => (string) $name, 'label' => (string) ($field['label'] ?? $name),
                'values' => array_values(array_filter($field['values'] ?? [], static fn($value): bool => is_string($value) || is_numeric($value)))];
        }
        $percent = (int) $enrollment->required_total > 0
            ? min($enrollment->completed_at ? 100 : 99, (int) floor(100 * (int) $enrollment->completed_total / (int) $enrollment->required_total)) : 0;
        if (!$blocker && !$scheduleBlocked && $lesson->kind === 'audio') {
            $resumeQuery = $this->db->createQuery()->select('resume_ms')->from('#__jlms_lesson_progress')
                ->where('enrollment_id = :resumeEnrollment')->where('lesson_id = :resumeLesson')
                ->bind(':resumeEnrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':resumeLesson', (int) $lesson->id, ParameterType::INTEGER);
            $lesson->resume_ms = (int) ($this->db->setQuery($resumeQuery)->loadResult() ?: 0);
            $availableAt = gmdate('Y-m-d H:i:s.u');
            $audioQuery = $this->db->createQuery()->select('a.id, a.title, f.mime_type')->from('#__jlms_attachments AS a')
                ->innerJoin('#__jlms_assets AS f ON f.id = a.asset_id')->where('a.course_version_id = :version')->where('a.lesson_id = :lesson')
                ->where('a.state = 1')->where('a.preview = 0')->where("f.status = 'ready'")->where("f.mime_type IN ('audio/mpeg', 'audio/ogg')")
                ->where('(a.publish_up IS NULL OR a.publish_up <= :audioUp) AND (a.publish_down IS NULL OR a.publish_down > :audioDown)')
                ->bind(':audioUp', $availableAt)->bind(':audioDown', $availableAt)->order('a.ordering, a.id')
                ->bind(':version', $versionId, ParameterType::INTEGER)->bind(':lesson', (int) $lesson->id, ParameterType::INTEGER);
            $audio = $this->db->setQuery($audioQuery, 0, 2)->loadObjectList();
            if (count($audio) >= 1) { $lesson->audio_attachment = ['id' => (int) $audio[0]->id, 'title' => $audio[0]->title, 'mime' => $audio[0]->mime_type]; }
        }
        return ['enrollment_id' => $enrollmentId, 'course_id' => (int) $course->id, 'title' => $version->title_snapshot,
            'lesson' => (object) ['id' => (int) $lesson->id, 'title' => $lesson->title, 'kind' => $lesson->kind,
                'prerequisite_blocked' => $blocker !== null, 'schedule_blocked' => $scheduleBlocked,
                'schedule_state' => $selectedScheduleState,
                'schedule_available_at' => $selectedScheduleState === 'upcoming' ? LessonAvailability::displayEffectiveStart($lesson->publish_up, $lesson->release_after_days, $enrollment->started_at, $timezone) : '',
                'schedule_after_days' => $selectedScheduleState === 'awaiting_start' ? (int) $lesson->release_after_days : 0,
                'blocked_by_id' => $blocker ? (int) $blocker->required_id : 0,
                'blocked_by_title' => $blocker ? (string) $blocker->required_title : '',
                'provider_key' => $lesson->provider_key, 'duration_ms' => $lesson->duration_ms,
                'resume_ms' => (int) ($lesson->resume_ms ?? 0),
                'body' => \Joomla\CMS\Filter\InputFilter::getInstance([], [], 1, 1)->clean((string) ($lesson->body ?? ''), 'HTML'),
                'custom_fields' => $customFields],
            'outline' => $outline, 'percent' => $percent, 'completed_at' => $enrollment->completed_at];
    }

    /** Read-only, freshly authorized resume target. Does not start an attempt or change progress. */
    public function resumeTarget(User $identity, string $uiLanguage, int $enrollmentId): array
    {
        $actor = $this->freshActor($identity); $owner = (int) $actor->id;
        $q = $this->db->createQuery()->select('c.language')->from('#__jlms_enrollments AS e')
            ->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')->where('e.id = :enrollment')->where('e.user_id = :owner')
            ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':owner', $owner, ParameterType::INTEGER);
        $language = $this->db->setQuery($q)->loadResult();
        if ($language === null) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        $language = $language === '*' ? $uiLanguage : (string) $language;
        if (!isset(LearningLanguages::available($actor, $uiLanguage)[$language])) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        [$course, $version, , $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId);
        $lessonId = (int) $enrollment->last_lesson_id;
        if ($lessonId) {
            try {
                $last = $this->lesson((int) $version->id, $lessonId);
                $this->assertLessonAvailable($last, $enrollment);
            }
            catch (\RuntimeException $error) {
                if (!in_array($error->getMessage(), ['COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_LESSON_NOT_YET_AVAILABLE', 'COM_JLMS_LESSON_CLOSED'], true)) { throw $error; }
                $lessonId = 0;
            }
        }
        if (!$lessonId) {
            $versionId = (int) $version->id;
            $completedState = 'completed';
            $q = $this->db->createQuery()->select('l.id')->from('#__jlms_lessons AS l')
                ->innerJoin('#__jlms_modules AS m ON m.id = l.module_id AND m.course_version_id = l.course_version_id')
                ->leftJoin('#__jlms_lesson_progress AS p ON p.lesson_id = l.id AND p.enrollment_id = :enrollment')
                ->where('l.course_version_id = :version')->where('l.required = 1')
                ->where('(p.state IS NULL OR p.state <> :completed)')
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':version', $versionId, ParameterType::INTEGER)
                ->bind(':completed', $completedState)
                ->order('m.ordering, m.id, l.ordering, l.id');
            $lessonId = (int) $this->db->setQuery($q, 0, 1)->loadResult();
            if (!$lessonId) {
                // When all required lessons are complete, Resume can still reopen the first lesson for review.
                $q = $this->db->createQuery()->select('l.id')->from('#__jlms_lessons AS l')
                    ->innerJoin('#__jlms_modules AS m ON m.id = l.module_id AND m.course_version_id = l.course_version_id')
                    ->where('l.course_version_id = :version')->bind(':version', $versionId, ParameterType::INTEGER)
                    ->order('m.ordering, m.id, l.ordering, l.id');
                $lessonId = (int) $this->db->setQuery($q, 0, 1)->loadResult();
            }
            $this->lesson($versionId, $lessonId);
        }
        return ['enrollment_id' => $enrollmentId, 'lesson_id' => $lessonId, 'language' => $language];
    }

    public function complete(User $identity, string $language, int $enrollmentId, int $lessonId, string $key): void
    {
        self::key($key);
        $actor = $this->freshActor($identity);
        $this->transaction(function () use ($actor, $language, $enrollmentId, $lessonId, $key): void {
            [, $version, , $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId, true);
            $lesson = $this->lesson((int) $version->id, $lessonId);
            $this->assertLessonAvailable($lesson, $enrollment);
            $this->assertPrerequisitesComplete($enrollmentId, $lessonId, $version);
            if (!in_array($lesson->kind, ['text', 'document', 'audio'], true) || $lesson->completion_rule !== 'manual') { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
            $this->ledger($actor, 'lesson.complete', $key, ['enrollment_id' => $enrollmentId, 'lesson_id' => $lessonId], $enrollmentId);
            $q = $this->db->createQuery()->select('state')->from('#__jlms_lesson_progress')
                ->where('enrollment_id = :enrollment')->where('lesson_id = :lesson')
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':lesson', $lessonId, ParameterType::INTEGER);
            if ($this->db->setQuery($q)->loadResult() === 'completed') {
                return;
            }
            $now = gmdate('Y-m-d H:i:s');
            $progress = (object) ['enrollment_id' => $enrollmentId, 'course_version_id' => (int) $version->id,
                'lesson_id' => $lessonId, 'source_revision' => (int) $lesson->source_revision, 'coverage_json' => '[]',
                'state' => 'completed', 'completed_at' => $now, 'last_activity_at' => $now, 'revision' => 1];
            // No other lesson-progress writer exists in this increment; the enrollment lock serialises all completions.
            $this->db->insertObject('#__jlms_lesson_progress', $progress);
            $total = (int) $enrollment->completed_total + (int) $lesson->required;
            $update = (object) ['id' => $enrollmentId, 'completed_total' => $total,
                'started_at' => $enrollment->started_at ?: $now, 'completed_at' => $enrollment->completed_at,
                'last_activity_at' => $now, 'last_lesson_id' => $lessonId, 'revision' => (int) $enrollment->revision + 1,
                'modified_by' => (int) $actor->id, 'modified_at' => $now];
            if ($total >= (int) $enrollment->required_total && (int) $enrollment->required_total > 0) {
                $update->completed_at = $enrollment->completed_at ?: $now;
            }
            $this->db->updateObject('#__jlms_enrollments', $update, 'id', true);
        });
    }

    /** Persist an audio seek point only; this never grants lesson or course completion. */
    public function saveAudioResume(User $identity, string $language, int $enrollmentId, int $lessonId, int $positionMs): int
    {
        if ($positionMs < 0 || $positionMs > 28800000) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $actor = $this->freshActor($identity);
        return $this->transaction(function () use ($actor, $language, $enrollmentId, $lessonId, $positionMs): int {
            [, $version, , $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId, true);
            $lesson = $this->lesson((int) $version->id, $lessonId);
            $this->assertLessonAvailable($lesson, $enrollment);
            $this->assertPrerequisitesComplete($enrollmentId, $lessonId, $version);
            if ($lesson->kind !== 'audio') { throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403); }
            $q = $this->db->createQuery()->select('*')->from('#__jlms_lesson_progress')
                ->where('enrollment_id = :enrollment')->where('lesson_id = :lesson')
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':lesson', $lessonId, ParameterType::INTEGER);
            // Joomla's DatabaseQuery does not expose forUpdate() on supported drivers.
            $q->setQuery((string) $q . ' FOR UPDATE');
            $progress = $this->db->setQuery($q)->loadObject();
            $now = gmdate('Y-m-d H:i:s');
            if (!$progress) {
                $progress = (object) ['enrollment_id' => $enrollmentId, 'course_version_id' => (int) $version->id,
                    'lesson_id' => $lessonId, 'source_revision' => (int) $lesson->source_revision,
                    'coverage_json' => '[]', 'resume_ms' => $positionMs, 'state' => 'started',
                    'completed_at' => null, 'last_activity_at' => $now, 'revision' => 1];
                $this->db->insertObject('#__jlms_lesson_progress', $progress);
            } else {
                $this->db->updateObject('#__jlms_lesson_progress', (object) ['enrollment_id' => $enrollmentId,
                    'lesson_id' => $lessonId, 'resume_ms' => $positionMs, 'last_activity_at' => $now,
                    'revision' => (int) $progress->revision + 1], ['enrollment_id', 'lesson_id']);
            }
            $this->db->updateObject('#__jlms_enrollments', (object) ['id' => $enrollmentId,
                'started_at' => $enrollment->started_at ?: $now, 'last_activity_at' => $now,
                'last_lesson_id' => $lessonId, 'revision' => (int) $enrollment->revision + 1,
                'modified_by' => (int) $actor->id, 'modified_at' => $now], 'id', true);
            return $positionMs;
        });
    }
}
