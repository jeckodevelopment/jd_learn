<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue;

/** Course-scoped operational messages. Recipient IDs are revalidated when each job batch runs. */
final class CourseCommunicationService
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly LearningService $learning,
        private readonly EnrollmentRosterService $roster,
    ) {}

    private function authorized(User $identity, int $courseId): User
    {
        $actor = $this->learning->freshActor($identity);
        $this->roster->course($actor, $courseId);
        return $actor;
    }

    /** @return array{course: object, recipients: array<int, object>} */
    public function editor(User $identity, int $courseId): array
    {
        $this->authorized($identity, $courseId);
        $courseQuery = $this->db->createQuery()->select('id,title')->from('#__jlms_courses')
            ->where('id = :course')->bind(':course', $courseId, ParameterType::INTEGER);
        $course = $this->db->setQuery($courseQuery)->loadObject()
            ?: throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        $query = $this->db->createQuery()->select('e.id AS enrollment_id,u.name,u.username,u.email')
            ->from('#__jlms_enrollments AS e')->innerJoin('#__users AS u ON u.id=e.user_id')
            ->where('e.course_id=:course')->where("e.status='active'")->where('u.block=0')
            ->bind(':course', $courseId, ParameterType::INTEGER)->order('u.name,u.id');
        $recipients = [];
        foreach ($this->db->setQuery($query, 0, 2000)->loadObjectList() as $row) {
            if (!filter_var((string) $row->email, FILTER_VALIDATE_EMAIL)) { continue; }
            unset($row->email);
            $recipients[] = $row;
        }
        return ['course' => $course, 'recipients' => $recipients];
    }

    /** @param array<int, mixed> $enrollmentIds */
    public function preview(User $identity, int $courseId, string $subject, string $body, array $enrollmentIds, array $template = []): array
    {
        $this->authorized($identity, $courseId);
        $subject = trim($subject);
        $body = trim($body);
        if ($subject === '' || mb_strlen($subject) > 300 || preg_match('/[\x00-\x1f\x7f]/', $subject) || $body === '' || mb_strlen($body) > 16000 || count($enrollmentIds) < 1 || count($enrollmentIds) > 2000) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $ids = [];
        foreach ($enrollmentIds as $value) {
            if (!is_scalar($value) || !preg_match('/^[1-9][0-9]{0,9}$/', (string) $value)) {
                throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
            }
            $ids[] = (int) $value;
        }
        $ids = array_values(array_unique($ids));
        if (count($ids) !== count($enrollmentIds)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $ids = $this->activeIds($courseId, $ids);
        if (count($ids) !== count($enrollmentIds)) { throw new \RuntimeException('COM_JLMS_COMMUNICATION_RECIPIENTS_CHANGED', 409); }
        sort($ids);
        $course = $this->editor($identity, $courseId)['course'];
        $data = $this->templateData('Destinatario di esempio', (string) $course->title, $subject, $body);
        $snapshot = CourseMessageTemplate::validate(
            $template !== [] ? $template : CourseMessageTemplate::forComposition('*'), $data
        );
        $renderer = new CourseMessageMailer();
        $rendered = [
            'subject' => $renderer->render($snapshot['subject'], $data),
            'body' => $renderer->render($snapshot['body'], $data),
            'htmlbody' => $snapshot['htmlbody'] !== ''
                ? $renderer->render($snapshot['htmlbody'], $data, true) : '',
        ];
        if (trim($rendered['subject']) === '' || mb_strlen($rendered['subject']) > 300
            || preg_match('/[\\x00-\\x1f\\x7f]/', $rendered['subject'])) {
            throw new \RuntimeException('COM_JLMS_COMMUNICATION_TEMPLATE_INVALID', 422);
        }
        $hash = hash('sha256', json_encode([$courseId, $subject, $body, $ids, $snapshot], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return ['course_id' => $courseId,'subject' => $subject,'body' => $body,'template' => $snapshot,
            'rendered' => $rendered,'enrollment_ids' => $ids,'count' => count($ids),'hash' => $hash];
    }

    /** Persist a confirmed job. No email addresses or recipient names are stored in the job payload. */
    public function queue(User $identity, int $courseId, string $subject, string $body, array $ids, string $hash, string $mutationId, array $template = []): int
    {
        if (!preg_match('/^[a-f0-9-]{36}$/i', $mutationId)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $plan = $this->preview($identity, $courseId, $subject, $body, $ids, $template);
        if (!hash_equals($plan['hash'], $hash)) { throw new \RuntimeException('COM_JLMS_COMMUNICATION_CONFLICT', 409); }
        $actor = $this->authorized($identity, $courseId);
        $this->db->transactionStart();
        try {
            $created = false;
            $courseLock = $this->db->createQuery()->select('id')->from('#__jlms_courses')
                ->where('id=:course')->bind(':course', $courseId, ParameterType::INTEGER);
            $courseLock->setQuery((string) $courseLock . ' FOR UPDATE');
            $this->db->setQuery($courseLock)->loadResult();
            // Joomla DatabaseQuery::bind() receives its value by reference.
            // A cast expression here triggers a PHP Error and prevents queueing.
            $scopeCourseId = (string) $courseId;
            $ownerUserId = (int) $actor->id;
            // Same mutation identifier must mean the same immutable message,
            // including template snapshot and recipients. Otherwise a modified
            // retry could misleadingly return a previous job as its own.
            $expectedPayload = json_encode([
                'mutation_id' => $mutationId,
                'subject' => $plan['subject'],
                'body' => $plan['body'],
                'template' => $plan['template'],
                'enrollment_ids' => $plan['enrollment_ids'],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $existing = $this->db->createQuery()->select('id,payload_json')->from('#__jlms_jobs')
                ->where("kind='course_communication'")
                ->where("JSON_UNQUOTE(JSON_EXTRACT(scope_json, '$.mutation_id')) = :mutation")
                ->where("JSON_UNQUOTE(JSON_EXTRACT(scope_json, '$.course_id')) = :scopeCourse")
                ->where('owner_user_id = :owner')
                ->bind(':mutation', $mutationId)->bind(':scopeCourse', $scopeCourseId)->bind(':owner', $ownerUserId, ParameterType::INTEGER);
            $previous = $this->db->setQuery($existing)->loadObject();
            if ($previous !== null && !hash_equals((string) $previous->payload_json, $expectedPayload)) {
                throw new \RuntimeException('COM_JLMS_COMMUNICATION_CONFLICT', 409);
            }
            $jobId = (int) ($previous->id ?? 0);
            if (!$jobId) {
                $now = gmdate('Y-m-d H:i:s');
                $job = (object) [
                    'kind' => 'course_communication','owner_user_id' => (int) $actor->id,
                    'scope_json' => json_encode(['course_id' => $courseId,'mutation_id' => $mutationId], JSON_THROW_ON_ERROR),
                    'payload_json' => $expectedPayload,
                    'status' => 'queued','cursor_json' => json_encode(['offset' => 0], JSON_THROW_ON_ERROR),
                    'progress_done' => 0,'progress_total' => $plan['count'],'attempts' => 0,'available_at' => $now,'created_at' => $now,
                ];
                $this->db->insertObject('#__jlms_jobs', $job, 'id');
                $jobId = (int) $job->id;
                $created = true;
            }
            if ($created) {
                (new \Jlms\Component\Jlms\Administrator\Infrastructure\AssessmentAudit($this->db))->record($actor,
                    ['id' => $jobId,'course_id' => $courseId,'recipient_count' => $plan['count'],'event' => 'queued','correlation_id' => $mutationId], 'coursecommunication');
            }
            $this->db->transactionCommit();
            return $jobId;
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }
    }

    /** Test delivery is restricted to the authorized actor; roster addresses are never accepted. */
    public function test(User $identity, int $courseId, string $subject, string $body, string $mutationId, array $template = []): void
    {
        if (!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/iD', $mutationId)) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $actor = $this->authorized($identity, $courseId);
        $subject = trim($subject); $body = trim($body);
        if ($subject === '' || mb_strlen($subject) > 300 || preg_match('/[\x00-\x1f\x7f]/', $subject) || $body === '' || mb_strlen($body) > 16000 || !filter_var($actor->email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $course = $this->editor($actor, $courseId)['course'];
        $data = $this->templateData((string) $actor->name, (string) $course->title, $subject, $body);
        $data['__course_message_snapshot'] = CourseMessageTemplate::validate(
            $template !== [] ? $template : CourseMessageTemplate::forComposition('*'), $data
        );
        $key = CourseMessageMutation::testEventKey($courseId, (int) $actor->id, $mutationId);
        (new MailQueue($this->db))->enqueue($courseId, $key, 'com_jlms.course.message', (string) ($actor->getParam('language') ?: '*'), (string) $actor->email, (string) $actor->name, $data, (int) $actor->id);
    }

    /** Add queued outbox rows in small Scheduler batches; current course ACL and enrollment state are rechecked. */
    public function process(int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $jobs = $this->db->createQuery()->select('*')->from('#__jlms_jobs')
            ->where("kind='course_communication'")->where("status IN ('queued','running')")
            ->where('(lease_until IS NULL OR lease_until < UTC_TIMESTAMP(6))')->order('id')->setLimit(1);
        $job = $this->db->setQuery($jobs)->loadObject();
        if (!$job) { return ['queued' => 0,'processed' => 0,'failed' => 0]; }
        $token = bin2hex(random_bytes(32));
        $claim = $this->db->createQuery()->update('#__jlms_jobs')->set("status='running'")
            ->set('lease_token=' . $this->db->quote($token))->set('lease_until=DATE_ADD(UTC_TIMESTAMP(6), INTERVAL 5 MINUTE)')
            ->set('attempts=attempts+1')->where('id=' . (int) $job->id)
            ->where("status IN ('queued','running')")->where('(lease_until IS NULL OR lease_until < UTC_TIMESTAMP(6))');
        $this->db->setQuery($claim)->execute();
        if ($this->db->getAffectedRows() !== 1) { return ['queued' => 0,'processed' => 0,'failed' => 0]; }

        try {
            $scope = json_decode((string) $job->scope_json, true, 16, JSON_THROW_ON_ERROR);
            $payload = json_decode((string) $job->payload_json, true, 16, JSON_THROW_ON_ERROR);
            $cursor = json_decode((string) ($job->cursor_json ?: '{}'), true, 16, JSON_THROW_ON_ERROR);
            $courseId = (int) ($scope['course_id'] ?? 0);
            $actor = new User((int) $job->owner_user_id);
            $this->authorized($actor, $courseId);
            $all = $payload['enrollment_ids'] ?? null;
            if (!is_array($all) || !$all) { throw new \RuntimeException('COM_JLMS_COMMUNICATION_JOB_INVALID'); }
            $offset = max(0, (int) ($cursor['offset'] ?? 0));
            $slice = array_slice($all, $offset, $limit);
            foreach ($slice as $enrollmentId) {
                $recipient = $this->recipient($courseId, (int) $enrollmentId);
                if ($recipient) {
                    $templateData = $this->templateData((string) $recipient->name, (string) $recipient->course_title, (string) $payload['subject'], (string) $payload['body']);
                    if (isset($payload['template']) && is_array($payload['template'])) {
                        $templateData['__course_message_snapshot'] = $payload['template'];
                    }
                    $key = hash('sha256', 'course-communication:' . (int) $job->id . ':' . (int) $enrollmentId);
                    (new MailQueue($this->db))->enqueue($courseId, $key, 'com_jlms.course.message', (string) $recipient->language, (string) $recipient->email, (string) $recipient->name, $templateData, (int) $recipient->id);
                }
            }
            $next = $offset + count($slice);
            $done = min((int) $job->progress_total, $next);
            $update = $this->db->createQuery()->update('#__jlms_jobs')->set('cursor_json=' . $this->db->quote(json_encode(['offset' => $next], JSON_THROW_ON_ERROR)))
                ->set('progress_done=' . $done)->set('lease_token=NULL')->set('lease_until=NULL')->where('id=' . (int) $job->id)->where('lease_token=' . $this->db->quote($token));
            if ($next >= count($all)) { $update->set("status='succeeded'")->set('payload_json=' . $this->db->quote('{}'))->set('finished_at=UTC_TIMESTAMP(6)'); }
            else { $update->set("status='queued'")->set('available_at=UTC_TIMESTAMP(6)'); }
            $this->db->setQuery($update)->execute();
            return ['queued' => count($slice),'processed' => count($slice),'failed' => 0];
        } catch (\Throwable $error) {
            $update = $this->db->createQuery()->update('#__jlms_jobs')->set("status='failed'")
                ->set("error_code='course_communication_failed'")->set('lease_token=NULL')->set('lease_until=NULL')
                ->set('payload_json=' . $this->db->quote('{}'))->set('finished_at=UTC_TIMESTAMP(6)')
                ->where('id=' . (int) $job->id)->where('lease_token=' . $this->db->quote($token));
            $this->db->setQuery($update)->execute();
            return ['queued' => 0,'processed' => 0,'failed' => 1];
        }
    }

    /** @return array<string, string> */
    private function templateData(string $recipientName, string $courseTitle, string $subject, string $body): array
    {
        $site = (string) \Joomla\CMS\Factory::getApplication()->get('sitename');
        return [
            'USER_FULLNAME' => $recipientName,
            'USER_FULLNAME_HTML' => htmlspecialchars($recipientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'COURSE_TITLE' => $courseTitle,
            'COURSE_TITLE_HTML' => htmlspecialchars($courseTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'MESSAGE_SUBJECT' => $subject,
            'MESSAGE_BODY_TEXT' => $body,
            'MESSAGE_BODY_HTML' => nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false),
            'SITE_NAME' => $site,
            'SITE_NAME_HTML' => htmlspecialchars($site, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ];
    }

    private function activeIds(int $courseId, array $ids): array
    {
        if (!$ids) { return []; }
        $q = $this->db->createQuery()->select('e.id,u.email')->from('#__jlms_enrollments AS e')->innerJoin('#__users AS u ON u.id=e.user_id')
            ->where('e.course_id=:course')->where("e.status='active'")->where('u.block=0')->bind(':course', $courseId, ParameterType::INTEGER)
            ->where('e.id IN (' . implode(',', array_map('intval', $ids)) . ')')->order('e.id');
        $valid = [];
        foreach ($this->db->setQuery($q)->loadObjectList() as $row) {
            if (filter_var((string) $row->email, FILTER_VALIDATE_EMAIL)) { $valid[] = (int) $row->id; }
        }
        return $valid;
    }

    private function recipient(int $courseId, int $enrollmentId): ?object
    {
        $q = $this->db->createQuery()->select('u.id,u.name,u.email,u.block,u.params AS user_params,c.title AS course_title')
            ->from('#__jlms_enrollments AS e')->innerJoin('#__users AS u ON u.id=e.user_id')->innerJoin('#__jlms_courses AS c ON c.id=e.course_id')
            ->where('e.id=:enrollment')->where('e.course_id=:course')->where("e.status='active'")->where('u.block=0')
            ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':course', $courseId, ParameterType::INTEGER);
        $row = $this->db->setQuery($q)->loadObject();
        if (!$row) { return null; }
        if (!filter_var((string) $row->email, FILTER_VALIDATE_EMAIL)) { return null; }
        $params = json_decode((string) $row->user_params, true);
        $row->language = is_array($params) && is_string($params['language'] ?? null) && $params['language'] !== '' ? $params['language'] : '*';
        return $row;
    }
}
