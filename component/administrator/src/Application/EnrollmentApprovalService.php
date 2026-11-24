<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue;

/** Course-scoped approval decisions. Requests never grant access before a committed decision. */
final class EnrollmentApprovalService
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly LearningService $learning,
        private readonly EnrollmentRosterService $roster,
        private readonly CatalogService $catalog,
        private readonly \Closure $audit,
    ) {
    }

    /** @return object[] Pending requests belonging only to a course the actor can manage. */
    public function pending(User $identity, int $courseId): array
    {
        $this->roster->course($identity, $courseId);
        $q = $this->db->createQuery()
            ->select('r.id, r.user_id, r.revision, r.created_at, u.name, u.username, v.title_snapshot')
            ->from('#__jlms_enrollment_requests AS r')
            ->innerJoin('#__jlms_editions AS e ON e.id = r.edition_id')
            ->innerJoin('#__jlms_course_versions AS v ON v.id = e.course_version_id')
            ->leftJoin('#__users AS u ON u.id = r.user_id')
            ->where('e.course_id = :course')
            ->where("r.status = 'pending'")
            ->bind(':course', $courseId, ParameterType::INTEGER)
            ->order('r.created_at ASC, r.id ASC');
        return $this->db->setQuery($q, 0, 100)->loadObjectList();
    }

    /** A decision is atomic with its enrollment, minimal audit and outbox event. */
    public function decide(
        User $identity,
        int $courseId,
        int $requestId,
        int $revision,
        string $decision,
        string $reason
    ): int {
        if ($courseId < 1 || $requestId < 1 || $revision < 1 || !in_array($decision, ['approved', 'rejected'], true)) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $reason = trim($reason);
        if (strlen($reason) > 500 || preg_match('/[\x00-\x1f\x7f<>]/', $reason) || !preg_match('//u', $reason)) {
            throw new \RuntimeException('COM_JLMS_ENROLLMENT_APPROVAL_REASON', 422);
        }
        // Authorization before any lock and again after the edition serialization point.
        $actor = $this->learning->freshActor($identity);
        $this->roster->course($actor, $courseId);
        $q = $this->db->createQuery()->select('e.id')->from('#__jlms_enrollment_requests AS r')
            ->innerJoin('#__jlms_editions AS e ON e.id = r.edition_id')
            ->where('r.id = :request')->where('e.course_id = :course')
            ->bind(':request', $requestId, ParameterType::INTEGER)->bind(':course', $courseId, ParameterType::INTEGER);
        $editionId = (int) $this->db->setQuery($q)->loadResult();
        if (!$editionId) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }

        $this->db->transactionStart();
        try {
            // Same edition lock as automatic/manual admission: no overbooking under concurrency.
            $q = $this->db->createQuery()->select('*')->from('#__jlms_editions')
                ->where('id = :id')->bind(':id', $editionId, ParameterType::INTEGER);
            $q->setQuery((string) $q . ' FOR UPDATE');
            $edition = $this->db->setQuery($q)->loadObject();
            if (!$edition || (int) $edition->course_id !== $courseId) {
                throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            }
            $actor = $this->learning->freshActor($actor);
            $this->roster->course($actor, $courseId);
            $q = $this->db->createQuery()->select('*')->from('#__jlms_enrollment_requests')
                ->where('id = :id')->where('edition_id = :edition')
                ->bind(':id', $requestId, ParameterType::INTEGER)->bind(':edition', $editionId, ParameterType::INTEGER);
            $q->setQuery((string) $q . ' FOR UPDATE');
            $request = $this->db->setQuery($q)->loadObject();
            if (!$request) {
                throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            }
            if ($request->status !== 'pending' || (int) $request->revision !== $revision) {
                throw new \RuntimeException('COM_JLMS_ENROLLMENT_APPROVAL_CONFLICT', 409);
            }
            $learner = new User((int) $request->user_id);
            $learner = $this->learning->freshActor($learner);
            if ((int) $learner->id !== (int) $request->user_id) {
                throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
            }
            $q = $this->db->createQuery()->select('*')->from('#__jlms_courses')
                ->where('id = :course')->bind(':course', $courseId, ParameterType::INTEGER);
            $course = $this->db->setQuery($q)->loadObject();
            if (!$course) {
                throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            }
            if ($decision === 'approved') {
                if (
                    $course->enrollment_mode !== 'approval' || (int) $course->state !== 1
                    || (int) $course->admissions_open !== 1 || (int) $course->current_version_id !==
                         (int) $edition->course_version_id
                    || $edition->status !== 'open'
                ) {
                    throw new \RuntimeException('COM_JLMS_ENROLLMENT_APPROVAL_CONFLICT', 409);
                }
                $now = gmdate('Y-m-d H:i:s');
                if (
                    ($edition->starts_at !== null && $edition->starts_at > $now)
                    || ($edition->ends_at !== null && $edition->ends_at <= $now)
                ) {
                    throw new \RuntimeException('COM_JLMS_LEARNING_WINDOW', 403);
                }
                $lang = (string) $course->language === '*' ? (string) (\Joomla\CMS\Factory::getApplication()
                    ->getLanguage()->getTag()) : (string) $course->language;
                // Publication, category hierarchy and Joomla access levels are rechecked for the learner.
                $visible = $this->catalog->course($learner, $lang, $courseId);
                if (
                    !in_array((int) $visible->content_access, array_map('intval', $learner
                    ->getAuthorisedViewLevels()), true)
                ) {
                    throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
                }
                $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_enrollments')
                    ->where('edition_id = :edition')->where("status <> 'cancelled'")
                    ->bind(':edition', $editionId, ParameterType::INTEGER);
                if (
                    $edition->capacity !== null && (int) $this->db->setQuery($q)->loadResult() >= (int) $edition
                    ->capacity
                ) {
                    throw new \RuntimeException('COM_JLMS_EDITION_FULL', 409);
                }
                $subjectKey = (string) $request->subject_key;
                $q = $this->db->createQuery()->select('id')->from('#__jlms_enrollments')
                    ->where('edition_id = :edition')->where('subject_key = :subject')
                    ->bind(':edition', $editionId, ParameterType::INTEGER)->bind(':subject', $subjectKey);
                if ($this->db->setQuery($q)->loadResult()) {
                    throw new \RuntimeException('COM_JLMS_ENROLLMENT_APPROVAL_CONFLICT', 409);
                }
                $versionId = (int) $edition->course_version_id;
                $q = $this->db->createQuery()->select('rules_json')->from('#__jlms_course_versions')
                    ->where('id = :version')->where("status = 'published'")
                    ->bind(':version', $versionId, ParameterType::INTEGER);
                $rulesJson = $this->db->setQuery($q)->loadResult();
                if (!is_string($rulesJson)) {
                    throw new \RuntimeException('COM_JLMS_ENROLLMENT_APPROVAL_CONFLICT', 409);
                }
                $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode($rulesJson);
                $q = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_lessons')
                    ->where('course_version_id = :version')->where('required = 1')
                    ->bind(':version', $versionId, ParameterType::INTEGER);
                $total = (int) $this->db->setQuery($q)->loadResult();
                if ($total < 1 && $rules['mode'] !== 'consultation') {
                    throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
                }
                $this->learning->assertEnrollmentScheduleReachable($courseId, (int) $edition->
                    course_version_id, $editionId, $edition->starts_at, $edition->ends_at);
                $enrollment = (object) [
                    'user_id' => (int) $learner->id, 'subject_key' => (string) $request->subject_key,
                    'course_id' => $courseId, 'course_version_id' => (int) $edition->course_version_id,
                    'edition_id' => $editionId, 'status' => 'active', 'origin' => 'approval',
                    'required_total' => $total, 'completed_total' => 0, 'revision' => 1,
                    'created_by' => (int) $actor->id, 'modified_by' => (int) $actor->id,
                    'created_at' => $now, 'modified_at' => $now,
                ];
                $this->db->insertObject('#__jlms_enrollments', $enrollment, 'id');
                $enrollmentId = (int) $enrollment->id;
            } else {
                $enrollmentId = 0;
                $now = gmdate('Y-m-d H:i:s');
            }

            $updatedRequest = (object) [
                'id' => $requestId, 'status' => $decision, 'reason' => $reason ?: null,
                'decided_by' => (int) $actor->id, 'decided_at' => $now,
                'revision' => $revision + 1, 'modified_by' => (int) $actor->id, 'modified_at' => $now,
            ];
            $this->db->updateObject('#__jlms_enrollment_requests', $updatedRequest, 'id');
            ($this->audit)($actor, [
                'id' => $requestId, 'actor_id' => (int) $actor->id, 'course_id' => $courseId,
                'reason_reference' => $requestId, 'reason_code' => 'enrollment_request_' . $decision,
                'correlation_id' => bin2hex(random_bytes(16)),
            ]);
            $template = $decision === 'approved' ? 'com_jlms.enrollment.approved' : 'com_jlms.enrollment.rejected';
            $data = [
                'USER_FULLNAME' => trim((string) $learner->name), 'COURSE_TITLE' => (string) $course->title,
                'SITE_NAME' => (string) \Joomla\CMS\Factory::getApplication()->get('sitename'),
            ];
            if ($enrollmentId) {
                $data['DASHBOARD_URL'] = \Joomla\CMS\Uri\Uri::root() . 'index.php?option=com_jlms&view=dashboard';
            }
            (new MailQueue($this->db))->enqueue(
                $courseId,
                hash('sha256', 'enrollment.decision:' . $requestId),
                $template,
                (string) ($learner->getParam('language', '') ?: $course->language),
                (string) $learner->email,
                trim((string) $learner->name),
                $data,
                (int) $learner->id
            );
            $this->db->transactionCommit();
            return $enrollmentId;
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }
    }
}
