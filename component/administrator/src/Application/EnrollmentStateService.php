<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;

/** Explicit pedagogical suspension/reactivation. Never grants Joomla groups or resets learning history. */
final class EnrollmentStateService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning,
        private readonly EnrollmentRosterService $roster, private readonly \Closure $audit) {}

    private function scope(User $identity, int $course, int $enrollment): array
    {
        $actor = $this->learning->freshActor($identity);
        $c = $this->roster->course($actor, $course);
        $q = $this->db->createQuery()->select('e.*, u.name AS learner_name, u.email AS learner_email, c.title AS course_title, c.language AS course_language, c.params_json AS course_params')
            ->from('#__jlms_enrollments AS e')->leftJoin('#__users AS u ON u.id = e.user_id')
            ->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')->where('e.id = :enrollment')->where('e.course_id = :course')
            ->bind(':enrollment', $enrollment, ParameterType::INTEGER)->bind(':course', $course, ParameterType::INTEGER);
        $e = $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        return [$actor, $c, $e];
    }

    private function latest(int $enrollment): ?object
    {
        $q = $this->db->createQuery()->select('revision_no')->from('#__jlms_enrollment_state_changes')
            ->where('enrollment_id = :enrollment')->bind(':enrollment', $enrollment, ParameterType::INTEGER)->order('revision_no DESC');
        return $this->db->setQuery($q, 0, 1)->loadObject() ?: null;
    }

    private function plan(object $e, string $target): array
    {
        if (!in_array($target, ['active', 'suspended'], true)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $id = (int) $e->id;
        // All quiz writers acquire the enrollment lock first, so this projection is stable during apply.
        $q = $this->db->createQuery()->select('a.id, a.revision, a.deadline_at')->from('#__jlms_active_attempts AS s')
            ->innerJoin('#__jlms_attempts AS a ON a.id = s.attempt_id AND a.enrollment_id = s.enrollment_id')
            ->where('s.enrollment_id = :enrollment')->where("a.status = 'open'")->bind(':enrollment', $id, ParameterType::INTEGER)->order('a.id');
        $attempts = $this->db->setQuery($q, 0, 1001)->loadAssocList();
        $last = $this->latest($id); $revision = $last ? (int) $last->revision_no : 0;
        $allowed = $target === 'suspended' ? in_array($e->status, ['active', 'completed'], true) : $e->status === 'suspended';
        $p = ['enrollment_id' => $id, 'enrollment_revision' => (int) $e->revision, 'history_revision' => $revision,
            'previous_status' => $e->status, 'target_status' => $target, 'completed_at' => $e->completed_at,
            'completed_total' => (int) $e->completed_total, 'required_total' => (int) $e->required_total,
            'starts_at' => $e->starts_at, 'ends_at' => $e->ends_at, 'open_attempts' => count($attempts),
            'blocked' => !$allowed || $revision >= 10000 || count($attempts) > 1000 ? 'COM_JLMS_ENROLLMENT_STATE_BLOCKED' : null];
        $p['hash'] = hash('sha256', QuestionDefinition::canonical(['impact' => $p, 'attempts' => $attempts]));
        return $p;
    }

    public function editor(User $identity, int $course, int $enrollment, string $target = ''): array
    {
        [, $c, $e] = $this->scope($identity, $course, $enrollment);
        if ($target === '') { $target = $e->status === 'suspended' ? 'active' : 'suspended'; }
        $q = $this->db->createQuery()->select('id, revision_no, previous_status, target_status, reason, actor_user_id, created_at')
            ->from('#__jlms_enrollment_state_changes')->where('enrollment_id = :enrollment')->bind(':enrollment', $enrollment, ParameterType::INTEGER)->order('revision_no DESC');
        // Never expose the internal enrollment row (subject key etc.) to the view.
        return ['course' => $c, 'enrollment_id' => $enrollment, 'learner_name' => (string) ($e->learner_name ?? ''),
            'impact' => $this->plan($e, $target), 'history' => $this->db->setQuery($q, 0, 20)->loadObjectList()];
    }

    public function apply(User $identity, int $course, int $enrollment, int $expected, string $target,
        string $reason, string $key, string $impactHash, bool $confirmed): int
    {
        if (!$confirmed || $expected < 1 || !in_array($target, ['active', 'suspended'], true)
            || !preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D', $key)
            || !preg_match('/\A[a-f0-9]{64}\z/D', $impactHash)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $reason = trim($reason);
        if ($reason === '' || strlen($reason) > 500 || preg_match('/[\x00-\x1f\x7f<>]/', $reason) || !preg_match('//u', $reason)) {
            throw new \RuntimeException('COM_JLMS_ENROLLMENT_STATE_REASON', 422);
        }
        $this->scope($identity, $course, $enrollment);
        $this->db->transactionStart();
        try {
            $q = $this->db->createQuery()->select('id')->from('#__jlms_enrollments')->where('id = :id')->where('course_id = :course')
                ->bind(':id', $enrollment, ParameterType::INTEGER)->bind(':course', $course, ParameterType::INTEGER);
            $q->setQuery((string) $q . ' FOR UPDATE');
            if (!$this->db->setQuery($q)->loadResult()) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
            [$actor, , $e] = $this->scope($identity, $course, $enrollment);
            $hash = hash('sha256', QuestionDefinition::canonical(['actor' => (int) $actor->id, 'course' => $course, 'enrollment' => $enrollment,
                'revision' => $expected, 'target' => $target, 'reason' => $reason, 'impact' => $impactHash]));
            $q = $this->db->createQuery()->select('id, request_hash')->from('#__jlms_enrollment_state_changes')
                ->where('enrollment_id = :enrollment')->where('mutation_id = :mutation')->bind(':enrollment', $enrollment, ParameterType::INTEGER)->bind(':mutation', $key);
            $old = $this->db->setQuery($q)->loadObject();
            if ($old) {
                if (!hash_equals($old->request_hash, $hash)) { throw new \RuntimeException('COM_JLMS_ENROLLMENT_STATE_CONFLICT', 409); }
                $this->db->transactionCommit(); return (int) $old->id;
            }
            $p = $this->plan($e, $target);
            if ($p['blocked'] !== null) { throw new \RuntimeException($p['blocked'], 409); }
            if ((int) $e->revision !== $expected || !hash_equals($p['hash'], $impactHash)) { throw new \RuntimeException('COM_JLMS_ENROLLMENT_STATE_CONFLICT', 409); }
            $now = gmdate('Y-m-d H:i:s');
            $record = (object) ['enrollment_id' => $enrollment, 'course_version_id' => (int) $e->course_version_id,
                'revision_no' => $p['history_revision'] + 1, 'enrollment_revision' => $expected,
                'previous_status' => $e->status, 'target_status' => $target, 'impact_hash' => $impactHash,
                'reason' => $reason, 'actor_user_id' => (int) $actor->id, 'mutation_id' => $key, 'request_hash' => $hash, 'created_at' => $now];
            $this->db->insertObject('#__jlms_enrollment_state_changes', $record, 'id');
            $update = (object) ['id' => $enrollment, 'status' => $target, 'revision' => $expected + 1, 'modified_by' => (int) $actor->id, 'modified_at' => $now];
            $this->db->updateObject('#__jlms_enrollments', $update, 'id');
            ($this->audit)($actor, ['id' => (int) $record->id, 'actor_id' => (int) $actor->id, 'enrollment_id' => $enrollment,
                'previous_status' => $e->status, 'target_status' => $target, 'reason_reference' => (int) $record->id,
                'reason_code' => 'enrollment_state_changed', 'correlation_id' => bin2hex(random_bytes(16))]);
            $template = $target === 'suspended' ? 'com_jlms.enrollment.suspended' : 'com_jlms.enrollment.reactivated';
            $name = trim((string) ($e->learner_name ?? ''));
            (new \Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue($this->db))->enqueue(
                $course, hash('sha256', 'enrollment.state:' . (int) $record->id), $template,
                (string) ($e->course_language ?: '*'), (string) ($e->learner_email ?? ''), $name,
                ['NAME' => $name, 'COURSE' => (string) $e->course_title,
                    'SITENAME' => (string) \Joomla\CMS\Factory::getApplication()->get('sitename')]
            );
            $this->db->transactionCommit(); return (int) $record->id;
        } catch (\Throwable $error) { $this->db->transactionRollback(); throw $error; }
    }
}
