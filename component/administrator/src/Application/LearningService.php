<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

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
        if (!(int) $actor->id || $actor->block || $actor->activation !== '') {
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

    private function course(User $actor, string $language, int $id): object
    {
        $course = $this->catalog->course($actor, $language, $id);
        if (!in_array((int) $course->content_access, array_map('intval', $actor->getAuthorisedViewLevels()), true)) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        return $course;
    }

    private function authorisedEnrollment(User $actor, string $language, int $id, bool $lock = false): array
    {
        $enrollment = $this->row('enrollments', $id, $lock);
        if ((int) $enrollment->user_id !== (int) $actor->id) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $course = $this->course($actor, $language, (int) $enrollment->course_id);
        $version = $this->row('course_versions', (int) $enrollment->course_version_id);
        $edition = $this->row('editions', (int) $enrollment->edition_id);
        if (!in_array($enrollment->status, ['active', 'completed'], true)
            || $version->status !== 'published' || (int) $version->course_id !== (int) $course->id
            || (int) $edition->course_id !== (int) $course->id
            || (int) $edition->course_version_id !== (int) $version->id || $edition->status !== 'open') {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        $rules = json_decode($version->rules_json, true, 32, JSON_THROW_ON_ERROR);
        if (($rules['mode'] ?? '') !== 'learning' || ($rules['sequence_mode'] ?? '') !== 'free'
            || ($rules['certificate_enabled'] ?? true) !== false || ($rules['completion_policy'] ?? '') !== 'all_required') {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        self::window($edition);
        self::window($enrollment);
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
        return $this->transaction(function () use ($actor, $language, $courseId, $course, $subject, $key): int {
            $versionId = (int) $course->current_version_id;
            $q = $this->db->createQuery()->select('id')->from('#__jlms_editions')->where('course_id = :course')
                ->where('course_version_id = :version')->where('code = :code')
                ->bind(':course', $courseId, ParameterType::INTEGER)->bind(':version', $versionId, ParameterType::INTEGER);
            $code = 'default-v' . (int) $this->row('course_versions', $versionId)->version_no;
            $q->bind(':code', $code);
            $edition = $this->row('editions', (int) $this->db->setQuery($q)->loadResult(), true);
            // Recheck availability after waiting for the edition lock.
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
            'publish_up', 'publish_down', 'release_after_days', 'source_revision'];
        if ($includeBody) {
            $columns[] = 'body';
        }
        $q = $this->db->createQuery()->select($this->db->quoteName($columns))->from('#__jlms_lessons')
            ->where('id = :lesson')->where('course_version_id = :version')
            ->bind(':lesson', $lessonId, ParameterType::INTEGER)->bind(':version', $versionId, ParameterType::INTEGER);
        $lesson = $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        if ((int) $lesson->course_version_id !== $versionId) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        if ($lesson->kind !== 'text' || $lesson->completion_rule !== 'manual' || $lesson->publish_up !== null
            || $lesson->publish_down !== null || $lesson->release_after_days !== null) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        return $lesson;
    }

    /** Reused by attachment policy: no lesson body, progress read or credit is produced. */
    public function resourceContext(User $identity, string $language, int $enrollmentId, int $lessonId = 0): array
    {
        $actor = $this->freshActor($identity);
        [$course, $version] = $this->authorisedEnrollment($actor, $language, $enrollmentId);
        if ($lessonId) { $this->lesson((int) $version->id, $lessonId); }
        return ['course_id' => (int) $course->id, 'version_id' => (int) $version->id];
    }

    public function classroom(User $identity, string $language, int $enrollmentId, int $lessonId = 0): array
    {
        $actor = $this->freshActor($identity);
        [$course, $version, $edition, $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId);
        $versionId = (int) $version->id;
        $q = $this->db->createQuery()->select('m.id AS module_id, m.title AS module_title, l.id, l.title, l.required, p.state')
            ->from('#__jlms_modules AS m')->innerJoin('#__jlms_lessons AS l ON l.module_id = m.id AND l.course_version_id = m.course_version_id')
            ->leftJoin('#__jlms_lesson_progress AS p ON p.lesson_id = l.id AND p.enrollment_id = :enrollment')
            ->where('m.course_version_id = :version')->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
            ->bind(':version', $versionId, ParameterType::INTEGER)->order('m.ordering, m.id, l.ordering, l.id');
        $outline = $this->db->setQuery($q, 0, 5000)->loadObjectList();
        if (!$outline) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $lessonId = $lessonId ?: (int) ($enrollment->last_lesson_id ?: $outline[0]->id);
        $lesson = $this->lesson($versionId, $lessonId, true);
        $percent = (int) $enrollment->required_total > 0
            ? min($enrollment->completed_at ? 100 : 99, (int) floor(100 * (int) $enrollment->completed_total / (int) $enrollment->required_total)) : 0;
        return ['enrollment_id' => $enrollmentId, 'course_id' => (int) $course->id, 'title' => $version->title_snapshot,
            'lesson' => (object) ['id' => (int) $lesson->id, 'title' => $lesson->title,
                'body' => \Joomla\CMS\Filter\InputFilter::getInstance([], [], 1, 1)->clean((string) $lesson->body, 'HTML')],
            'outline' => $outline, 'percent' => $percent, 'completed_at' => $enrollment->completed_at];
    }

    public function complete(User $identity, string $language, int $enrollmentId, int $lessonId, string $key): void
    {
        self::key($key);
        $actor = $this->freshActor($identity);
        $this->transaction(function () use ($actor, $language, $enrollmentId, $lessonId, $key): void {
            [, $version, , $enrollment] = $this->authorisedEnrollment($actor, $language, $enrollmentId, true);
            $lesson = $this->lesson((int) $version->id, $lessonId);
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
}
