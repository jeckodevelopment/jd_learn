<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Course-scoped administrative history. No write operation or access grant. */
final class EnrollmentRosterService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning) {}

    public function course(User $identity, int $courseId): object
    {
        $actor = $this->learning->freshActor($identity);
        if ($courseId < 1) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        if (!$actor->authorise('core.manage', 'com_jlms') || !$actor->authorise('jlms.enrollments.manage', 'com_jlms.course.' . $courseId)) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        $q = $this->db->createQuery()->select('id, title')->from('#__jlms_courses')
            ->where('id = :course')->bind(':course', $courseId, ParameterType::INTEGER);
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
    }

    private function optionalId(mixed $value): int
    {
        if ($value === 0 || $value === '0' || $value === '') { return 0; }
        try { return CurriculumService::id($value); }
        catch (\InvalidArgumentException) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
    }

    public function query(User $identity, int $courseId, array $filters = [])
    {
        $this->course($identity, $courseId);
        $version = $this->optionalId($filters['version'] ?? '0');
        $userId = $this->optionalId($filters['user'] ?? '0');
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? '';
        $progress = $filters['progress'] ?? '';
        if (!is_string($search)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $search = trim($search);
        $searchLength = preg_match_all('/./us', $search);
        if (strlen($search) > 400 || $searchLength === false || $searchLength > 100 || preg_match('/[\\x00-\\x1f\\x7f]/', $search)) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        if (!is_string($status) || !in_array($status, ['', 'requested', 'active', 'completed', 'suspended', 'migrated', 'expired', 'cancelled', 'rejected'], true)
            || !is_string($progress) || !in_array($progress, ['', 'not_started', 'in_progress', 'completed'], true)) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        if ($version) {
            $q = $this->db->createQuery()->select('id')->from('#__jlms_course_versions')
                ->where('course_id = :course')->where('id = :version')->bind(':course', $courseId, ParameterType::INTEGER)->bind(':version', $version, ParameterType::INTEGER);
            if (!$this->db->setQuery($q)->loadResult()) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        }
        $q = $this->db->createQuery()->select('e.id, e.course_version_id, e.edition_id, e.status, e.required_total, e.completed_total, e.started_at, e.completed_at, e.last_activity_at, e.starts_at, e.ends_at, u.name AS learner_name, u.username, u.block AS account_blocked, v.version_no, v.title_snapshot, c.current_version_id')
            ->from('#__jlms_enrollments AS e')
            ->leftJoin('#__users AS u ON u.id = e.user_id')
            ->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')
            ->leftJoin('#__jlms_course_versions AS v ON v.id = e.course_version_id AND v.course_id = e.course_id')
            ->where('e.course_id = :rosterCourse')->bind(':rosterCourse', $courseId, ParameterType::INTEGER);
        if ($version) { $q->where('e.course_version_id = :rosterVersion')->bind(':rosterVersion', $version, ParameterType::INTEGER); }
        if ($userId) { $q->where('e.user_id = :rosterUser')->bind(':rosterUser', $userId, ParameterType::INTEGER); }
        if ($search !== '') {
            // Escape LIKE metacharacters independently of SQL quoting; the value remains bound.
            $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $q->where('(u.name LIKE :rosterSearchName ESCAPE \'!\' OR u.username LIKE :rosterSearchUsername ESCAPE \'!\')')
                ->bind(':rosterSearchName', $pattern)->bind(':rosterSearchUsername', $pattern);
        }
        if ($status !== '') { $q->where('e.status = :rosterStatus')->bind(':rosterStatus', $status); }
        if ($progress === 'completed') { $q->where('e.completed_at IS NOT NULL'); }
        elseif ($progress === 'not_started') { $q->where('e.completed_at IS NULL AND e.started_at IS NULL AND e.last_activity_at IS NULL AND e.completed_total = 0'); }
        elseif ($progress === 'in_progress') { $q->where('e.completed_at IS NULL AND (e.started_at IS NOT NULL OR e.last_activity_at IS NOT NULL OR e.completed_total > 0)'); }
        return $q->order('e.id DESC');
    }
}
