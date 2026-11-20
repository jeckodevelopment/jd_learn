<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\CMS\Mail\MailTemplate;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\User\User;

/** Durable queue for core learner emails. */
final class MailQueue
{
    public function __construct(private DatabaseInterface $db)
    {
    }

    /** The unique event key makes repeated domain transitions harmless. */
    public function enqueue(int $courseId, string $key, string $template, string $language, string $email, string $name, array $data, ?int $recipientUserId = null): void
    {
        $event = match ($template) {
            'com_jlms.enrollment.confirmed' => 'enrollment',
            'com_jlms.enrollment.requested' => 'request',
            'com_jlms.enrollment.manager_request' => 'manager_request',
            'com_jlms.teacher.assigned' => 'teacher_assignment',
            'com_jlms.enrollment.approved' => 'approval',
            'com_jlms.enrollment.rejected' => 'rejection',
            'com_jlms.course.completed' => 'completion',
            'com_jlms.enrollment.suspended' => 'suspension',
            'com_jlms.enrollment.reactivated' => 'reactivation',
            'com_jlms.enrollment.cancelled' => 'cancellation',
            'com_jlms.certificate.available' => 'certificate',
            'com_jlms.course.message' => 'communication',
            'com_jlms.course.expiring' => 'expiry',
            'com_jlms.lesson.available' => 'lesson_release',
            'com_jlms.course.updated' => 'course_update', 
            default => null,
        };
        if ($courseId < 1 || $event === null || !preg_match('/^[a-f0-9]{64}$/', $key) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $courseQuery = $this->db->createQuery()->select('params_json')->from('#__jlms_courses')
            ->where('id = :id')->bind(':id', $courseId, ParameterType::INTEGER);
        $courseParams = $this->db->setQuery($courseQuery)->loadResult();
        if ($courseParams === null) {
            return;
        }
        $settings = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms');
        $globalEnabled = (bool) $settings->get('mail_' . $event . '_enabled', !in_array($event, ['lesson_release', 'course_update', 'manager_request'], true));
        if (!\Jlms\Component\Jlms\Administrator\Application\MailPreferences::enabled($event, $globalEnabled, (string) $courseParams)) {
            return;
        }

        $data = MailTemplateContext::enrich($this->db, $courseId, $data);

        $now = gmdate('Y-m-d H:i:s');
        $query = $this->db->createQuery()->insert('#__jlms_mail_queue')
            ->columns(['event_key', 'template_id', 'course_id', 'recipient_user_id', 'language', 'recipient_email', 'recipient_name', 'template_data', 'state', 'available_at', 'created_at'])
            ->values(implode(',', [
                $this->db->quote($key), $this->db->quote($template), (string) $courseId, $recipientUserId ? (string) $recipientUserId : 'NULL', $this->db->quote($language ?: '*'),
                $this->db->quote($email), $this->db->quote($name), $this->db->quote(json_encode($data, JSON_THROW_ON_ERROR)),
                $this->db->quote('pending'), $this->db->quote($now), $this->db->quote($now),
            ]));

        // A repeated event key is an expected idempotent no-op.
        try {
            $this->db->setQuery($query)->execute();
        } catch (\Throwable $error) {
            $check = $this->db->createQuery()->select('id')->from('#__jlms_mail_queue')
                ->where('event_key = :key')->bind(':key', $key);
            if (!(int) $this->db->setQuery($check)->loadResult()) {
                throw $error;
            }
        }
    }


    /** Queue one idempotent new-course notice for a confirmed consent revision. */
    public function enqueueAnnouncement(int $courseId, int $userId, string $consentAt, string $key, string $language, string $email, string $name, array $data): bool
    {
        if ($courseId < 1 || $userId < 1 || !preg_match('/^[a-f0-9]{64}$/', $key) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $paramsQuery = $this->db->createQuery()->select('params_json')->from('#__jlms_courses')->where('id = :course')
            ->bind(':course', $courseId, ParameterType::INTEGER);
        $params = $this->db->setQuery($paramsQuery)->loadResult();
        $global = (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('mail_announcement_enabled', true);
        if ($params === null || !\Jlms\Component\Jlms\Administrator\Application\MailPreferences::enabled('announcement', $global, (string) $params)) {
            return false;
        }
        $data = MailTemplateContext::enrich($this->db, $courseId, $data);
        $query = $this->db->createQuery()->insert('#__jlms_mail_queue')
            ->columns(['event_key','template_id','course_id','recipient_user_id','recipient_consent_at','language','recipient_email','recipient_name','template_data','state','available_at','created_at'])
            ->values(implode(',', [
                $this->db->quote($key), $this->db->quote('com_jlms.course.published'), (string) $courseId, (string) $userId,
                $this->db->quote($consentAt), $this->db->quote($language ?: '*'), $this->db->quote($email), $this->db->quote($name),
                $this->db->quote(json_encode($data, JSON_THROW_ON_ERROR)), $this->db->quote('pending'), 'UTC_TIMESTAMP(6)', 'UTC_TIMESTAMP(6)',
            ]));
        try {
            $this->db->setQuery($query)->execute();
            return true;
        } catch (\Throwable $error) {
            $check = $this->db->createQuery()->select('id')->from('#__jlms_mail_queue')->where('event_key = :key')->bind(':key', $key);
            if ((int) $this->db->setQuery($check)->loadResult()) {
                return false;
            }
            throw $error;
        }
    }

    /**
     * Recheck the current Joomla account before SMTP. Queue-time email addresses
     * are snapshots and must not be sent to after an account block or email edit.
     * This intentionally returns false for missing or unbound recipients.
     */
    public static function currentRecipient(DatabaseInterface $db, object $row): bool
    {
        $userId = (int) ($row->recipient_user_id ?? 0);
        $email = (string) ($row->recipient_email ?? '');
        if ($userId < 1 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $q = $db->createQuery()->select('id')->from('#__users')
            ->where('id = :recipient')->where('block = 0')
            ->where('LOWER(email) = LOWER(:email)')
            ->bind(':recipient', $userId, ParameterType::INTEGER)
            ->bind(':email', $email);
        return (bool) $db->setQuery($q)->loadResult();
    }

    /** Process a bounded batch; a claimed message is never automatically sent twice. */
    public function dispatch(int $limit = 25): array
    {
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $limit = max(1, min(100, $limit));
        $q = $this->db->createQuery()->select('id')->from('#__jlms_mail_queue')
            ->where("state IN ('pending','retry')")->where('available_at <= UTC_TIMESTAMP(6)')
            ->order('id ASC')->setLimit($limit);
        $ids = array_map('intval', $this->db->setQuery($q)->loadColumn());

        foreach ($ids as $id) {
            $claim = $this->db->createQuery()->update('#__jlms_mail_queue')
                ->set("state = 'sending'")->set('claimed_at = UTC_TIMESTAMP(6)')->set('attempts = attempts + 1')
                ->where('id = :id')->where("state IN ('pending','retry')")
                ->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($claim)->execute();
            if ($this->db->getAffectedRows() !== 1) {
                continue;
            }

            $q = $this->db->createQuery()->select('*')->from('#__jlms_mail_queue')->where('id = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $row = $this->db->setQuery($q)->loadObject();
            $invalidTemplate = false;
            try {
                // Communications must be linked to the Joomla user. For legacy
                // course messages with no recipient ID, fail closed rather than
                // send to a stale address after a possible account change.
                $linkedRecipient = (int) ($row->recipient_user_id ?? 0) > 0;
                if (($linkedRecipient || (string) $row->template_id === 'com_jlms.course.message')
                    && !self::currentRecipient($this->db, $row)) {
                    $skip = $this->db->createQuery()->update('#__jlms_mail_queue')
                        ->set("state = 'skipped'")->set("recipient_email = ''")
                        ->set("recipient_name = ''")->set("template_data = '{}'")
                        ->where('id = :id')->where("state = 'sending'")
                        ->bind(':id', $id, ParameterType::INTEGER);
                    $this->db->setQuery($skip)->execute();
                    $skipped++;
                    continue;
                }
                if ((string) $row->template_id === 'com_jlms.course.published' && !$this->announcementEligible($row)) {
                    $skip = $this->db->createQuery()->update('#__jlms_mail_queue')->set("state = 'skipped'")
                        ->set("recipient_email = ''")->set("recipient_name = ''")->set("template_data = '{}' ")
                        ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                    $this->db->setQuery($skip)->execute();
                    $skipped++;
                    continue;
                }
                if ((string) $row->template_id === 'com_jlms.teacher.assigned' && !$this->teacherAssignmentEligible($row)) {
                    // The assignment or the verified account may have changed since queueing.
                    $skip = $this->db->createQuery()->update('#__jlms_mail_queue')->set("state = 'skipped'")
                        ->set("recipient_email = ''")->set("recipient_name = ''")->set("template_data = '{}'")
                        ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                    $this->db->setQuery($skip)->execute();
                    $skipped++;
                    continue;
                }
                if ((string) $row->template_id === 'com_jlms.course.expiring' && !$this->expiryEligible($row)) {
                    $skip = $this->db->createQuery()->update('#__jlms_mail_queue')->set("state = 'skipped'")
                        ->set("recipient_email = ''")->set("recipient_name = ''")->set("template_data = '{}' ")
                        ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                    $this->db->setQuery($skip)->execute();
                    $skipped++;
                    continue;
                }
                if ((string) $row->template_id === 'com_jlms.lesson.available' && !$this->lessonReleaseEligible($row)) {
                    $skip = $this->db->createQuery()->update('#__jlms_mail_queue')->set("state = 'skipped'")
                        ->set("recipient_email = ''")->set("recipient_name = ''")->set("template_data = '{}'")
                        ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                    $this->db->setQuery($skip)->execute();
                    ++$skipped;
                    continue;
                }
                if ((string) $row->template_id === 'com_jlms.course.updated' && !$this->courseUpdateEligible($row)) {
                    $skip = $this->db->createQuery()->update('#__jlms_mail_queue')->set("state = 'skipped'")
                        ->set("recipient_email = ''")->set("recipient_name = ''")->set("template_data = '{}'")
                        ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                    $this->db->setQuery($skip)->execute();
                    ++$skipped;
                    continue;
                }
                if ((string) $row->template_id === 'com_jlms.enrollment.manager_request' && !$this->managerRequestEligible($row)) {
                    $skip = $this->db->createQuery()->update('#__jlms_mail_queue')
                        ->set("state = 'skipped'")->set("recipient_email = ''")
                        ->set("recipient_name = ''")->set("template_data = '{}'")
                        ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                    $this->db->setQuery($skip)->execute();
                    ++$skipped;
                    continue;
                }
                $data = json_decode((string) $row->template_data, true, 32, JSON_THROW_ON_ERROR);
                $templateId = (string) $row->template_id;
                $language = (string) $row->language;
                if ($templateId === 'com_jlms.course.message' && array_key_exists('__course_message_snapshot', $data)) {
                    // Send the exact editor snapshot from the confirmed job. The
                    // global Joomla template may have changed after queueing.
                    $snapshot = $data['__course_message_snapshot'];
                    unset($data['__course_message_snapshot']);
                    if (!is_array($snapshot)) {
                        $invalidTemplate = true;
                        throw new \RuntimeException('Invalid communication snapshot.');
                    }
                    try {
                        $snapshot = \Jlms\Component\Jlms\Administrator\Application\CourseMessageTemplate::validate($snapshot, $data);
                    } catch (\Throwable $invalid) {
                        $invalidTemplate = true;
                        throw $invalid;
                    }
                    if (!\Jlms\Component\Jlms\Administrator\Application\CourseMessageMailer::deliver(
                        $snapshot, $data, (string) $row->recipient_email, (string) $row->recipient_name
                    )) {
                        throw new \RuntimeException('Joomla mailer rejected the communication.');
                    }
                } else {
                    // Compatibility for legacy queued messages, without snapshots.
                    $selected = MailTemplate::getTemplate($templateId, $language)
                        ?? MailTemplate::getTemplate($templateId, '*');
                    if ($selected === null) {
                        $invalidTemplate = true;
                        throw new \RuntimeException('JD Learn mail template not registered.');
                    }
                    $unresolved = MailTemplatePlaceholderValidator::unresolved(
                        $templateId, $data, (string) $selected->subject,
                        (string) $selected->body, (string) $selected->htmlbody
                    );
                    if ($unresolved !== []) {
                        $invalidTemplate = true;
                        throw new \RuntimeException('JD Learn mail template has unresolved placeholders.');
                    }
                    $mail = new MailTemplate($templateId, $language);
                    $mail->addRecipient((string) $row->recipient_email, (string) $row->recipient_name);
                    $mail->addTemplateData($data);
                    $mail->addUnsafeTags(MailTemplateVariableCatalog::tagsRequiringHtmlEscape($templateId));
                    if (!$mail->send()) {
                        throw new \RuntimeException('Joomla mail template delivery returned false.');
                    }
                }
            } catch (\Throwable) {
                $attempts = (int) $row->attempts;
                $retry = !$invalidTemplate && $attempts < 5;
                $delay = min(3600, 60 * (2 ** min(6, $attempts - 1)));
                // DatabaseQuery::bind() accepts variables by reference, not a ternary
                // expression. Without this, a failed transport leaves a stale
                // 'sending' claim instead of recording retry/failed.
                $safeError = $invalidTemplate
                    ? 'Mail template is missing or contains unavailable placeholders.'
                    : 'Delivery failed; transport details are withheld.';
                $update = $this->db->createQuery()->update('#__jlms_mail_queue')
                    ->set($retry ? "state = 'retry'" : "state = 'failed'")
                    ->set($retry ? 'available_at = DATE_ADD(UTC_TIMESTAMP(6), INTERVAL ' . $delay . ' SECOND)' : 'available_at = available_at')
                    // Transport exceptions can contain addresses, SMTP responses, or provider details.
                    // Keep the outbox diagnostic useful without persisting them.
                    ->set('last_error = :error')->set('claimed_at = NULL')->where('id = :id')
                    ->bind(':error', $safeError)
                    ->bind(':id', $id, ParameterType::INTEGER);
                $this->db->setQuery($update)->execute();
                $failed++;
                continue;
            }

            // Keep this outside the mail exception handler. If SMTP accepted the message but
            // persistence fails, the row remains claimed and is not blindly sent a second time.
            $update = $this->db->createQuery()->update('#__jlms_mail_queue')
                ->set("state = 'sent'")->set('sent_at = UTC_TIMESTAMP(6)')->set('last_error = NULL')
                ->set("recipient_email = ''")->set("recipient_name = ''")->set("template_data = '{}' ")
                ->where('id = :id')->where("state = 'sending'")->bind(':id', $id, ParameterType::INTEGER);
            $this->db->setQuery($update)->execute();
            $sent++;
        }

        $this->db->setQuery("UPDATE #__jlms_course_announcement_campaigns AS campaign SET state = 'finished' WHERE campaign.state = 'queued' AND NOT EXISTS (SELECT 1 FROM #__jlms_mail_queue AS queued WHERE queued.course_id = campaign.course_id AND queued.template_id = 'com_jlms.course.published' AND queued.state IN ('pending','retry','sending'))")->execute();
        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped, 'processed' => count($ids)];
    }



    /** Pending status, course authorization, recipient address and preferences are fresh. */
    private function managerRequestEligible(object $row): bool
    {
        $userId = (int) ($row->recipient_user_id ?? 0);
        $courseId = (int) ($row->course_id ?? 0);
        if ($userId < 1 || $courseId < 1) { return false; }
        try {
            $data = json_decode((string) $row->template_data, true, 16, JSON_THROW_ON_ERROR);
            $requestId = (int) ($data['REQUEST_ID'] ?? 0);
            if ($requestId < 1) { return false; }
            $query = $this->db->createQuery()->select('r.id, r.user_id AS applicant_id, c.params_json')
                ->from('#__jlms_enrollment_requests AS r')
                ->innerJoin('#__jlms_editions AS ed ON ed.id = r.edition_id')
                ->innerJoin('#__jlms_courses AS c ON c.id = ed.course_id')
                ->innerJoin('#__users AS recipient ON recipient.id = :manager')
                ->where('r.id = :request')->where('c.id = :course')
                ->where("r.status = 'pending'")->where("ed.status = 'open'")
                ->where("c.enrollment_mode = 'approval'")->where('c.state = 1')
                ->where('c.admissions_open = 1')
                ->where('recipient.block = 0')
                ->where('LOWER(recipient.email) = LOWER(:email)')
                ->bind(':manager', $userId, ParameterType::INTEGER)
                ->bind(':course', $courseId, ParameterType::INTEGER)
                ->bind(':request', $requestId, ParameterType::INTEGER)
                ->bind(':email', (string) $row->recipient_email);
            $current = $this->db->setQuery($query)->loadObject();
            if (!$current || (int) $current->applicant_id === $userId
                || !\Jlms\Component\Jlms\Administrator\Application\MailPreferences::enabled(
                    'manager_request',
                    (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')
                        ->get('mail_manager_request_enabled', false),
                    (string) $current->params_json
                )) { return false; }
            return \Jlms\Component\Jlms\Administrator\Application\EnrollmentManagerNotice::authorized(
                $this->db, $courseId, $userId
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /** Ensure the learner is still entitled to the migrated course before SMTP. */
    private function courseUpdateEligible(object $row): bool
    {
        $userId = (int) ($row->recipient_user_id ?? 0);
        if ($userId < 1) { return false; }
        try {
            $payload = json_decode((string) $row->template_data, true, 16, JSON_THROW_ON_ERROR);
            $migrationId = (int) ($payload['MIGRATION_ID'] ?? 0);
            $targetId = (int) ($payload['TARGET_ENROLLMENT_ID'] ?? 0);
            if ($migrationId < 1 || $targetId < 1) { return false; }
            $query = $this->db->createQuery()->select(
                'target.course_version_id, source.course_version_id AS source_version_id, c.params_json, ' .
                'target.starts_at, target.ends_at, ed.starts_at AS edition_starts, ed.ends_at AS edition_ends'
            )->from('#__jlms_enrollment_migrations AS m')
                ->innerJoin('#__jlms_enrollments AS source ON source.id = m.source_enrollment_id')
                ->innerJoin('#__jlms_enrollments AS target ON target.id = m.target_enrollment_id AND target.user_id = source.user_id AND target.course_id = source.course_id')
                ->innerJoin('#__jlms_editions AS ed ON ed.id = target.edition_id AND ed.course_version_id = target.course_version_id')
                ->innerJoin('#__jlms_courses AS c ON c.id = target.course_id')
                ->innerJoin('#__jlms_course_versions AS v ON v.id = target.course_version_id AND v.course_id = target.course_id')
                ->innerJoin('#__users AS u ON u.id = target.user_id')
                ->where('m.id = :migration')->where('target.id = :target')->where('target.course_id = :course')
                ->where('target.user_id = :user')->where("source.status = 'migrated'")
                ->where("target.status = 'active'")->where('target.completed_at IS NULL')
                ->where("ed.status = 'open'")->where("v.status = 'published'")
                ->where('u.block = 0')->where('LOWER(u.email) = LOWER(:email)')
                ->where('c.state = 1')->where('c.delivery_enabled = 1')
                ->where('(target.starts_at IS NULL OR target.starts_at <= UTC_TIMESTAMP(6))')
                ->where('(target.ends_at IS NULL OR target.ends_at > UTC_TIMESTAMP(6))')
                ->where('(ed.starts_at IS NULL OR ed.starts_at <= UTC_TIMESTAMP(6))')
                ->where('(ed.ends_at IS NULL OR ed.ends_at > UTC_TIMESTAMP(6))')
                ->bind(':migration', $migrationId, ParameterType::INTEGER)
                ->bind(':target', $targetId, ParameterType::INTEGER)
                ->bind(':course', (int) $row->course_id, ParameterType::INTEGER)
                ->bind(':user', $userId, ParameterType::INTEGER)
                ->bind(':email', (string) $row->recipient_email);
            $record = $this->db->setQuery($query)->loadObject();
            if (!$record || !\Jlms\Component\Jlms\Administrator\Application\MailPreferences::enabled(
                'course_update',
                (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('mail_course_update_enabled', false),
                (string) $record->params_json
            )) { return false; }
            $user = new User($userId);
            $user->clearAccessRights();
            $language = preg_match('/^[a-z]{2}-[A-Z]{2}$/D', (string) $row->language) ? (string) $row->language : 'en-GB';
            $course = (new \Jlms\Component\Jlms\Administrator\Application\CatalogService($this->db))
                ->enrolledCourse($user, $language, (int) $row->course_id);
            if (!in_array((int) $course->content_access, array_map('intval', $user->getAuthorisedViewLevels()), true)) {
                return false;
            }
            return (new \Jlms\Component\Jlms\Administrator\Application\PublicationImpact($this->db))
                ->significantVersionChange((int) $record->source_version_id, (int) $record->course_version_id);
        } catch (\Throwable) {
            return false;
        }
    }

    /** Revalidate the exact version, learner, ACL, window and lesson release before SMTP. */
    private function lessonReleaseEligible(object $row): bool
    {
        $userId = (int) ($row->recipient_user_id ?? 0);
        if ($userId < 1) { return false; }
        try {
            $data = json_decode((string) $row->template_data, true, 16, JSON_THROW_ON_ERROR);
            $enrollmentId = (int) ($data['ENROLLMENT_ID'] ?? 0);
            $lessonId = (int) ($data['LESSON_ID'] ?? 0);
            if ($enrollmentId < 1 || $lessonId < 1) { return false; }
            $query = $this->db->createQuery()->select('e.id, e.created_at, e.started_at, e.starts_at, e.ends_at, e.course_version_id, ' .
                'ed.starts_at AS edition_starts, ed.ends_at AS edition_ends, v.published_at, ' .
                'l.publish_up, l.publish_down, l.release_after_days, c.params_json, c.content_access')
                ->from('#__jlms_enrollments AS e')
                ->innerJoin('#__jlms_editions AS ed ON ed.id = e.edition_id AND ed.course_version_id = e.course_version_id')
                ->innerJoin('#__jlms_course_versions AS v ON v.id = e.course_version_id AND v.course_id = e.course_id')
                ->innerJoin('#__jlms_lessons AS l ON l.id = :lesson AND l.course_version_id = e.course_version_id')
                ->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')
                ->innerJoin('#__users AS u ON u.id = e.user_id')
                ->where('e.id = :enrollment')->where('e.user_id = :user')->where('e.course_id = :course')
                ->where("e.status = 'active'")->where('e.completed_at IS NULL')
                ->where("ed.status = 'open'")->where("v.status = 'published'")
                ->where('u.block = 0')->where('LOWER(u.email) = LOWER(:email)')
                ->where('c.state = 1')->where('c.delivery_enabled = 1')
                ->where('(e.starts_at IS NULL OR e.starts_at <= UTC_TIMESTAMP(6))')
                ->where('(e.ends_at IS NULL OR e.ends_at > UTC_TIMESTAMP(6))')
                ->where('(ed.starts_at IS NULL OR ed.starts_at <= UTC_TIMESTAMP(6))')
                ->where('(ed.ends_at IS NULL OR ed.ends_at > UTC_TIMESTAMP(6))')
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':lesson', $lessonId, ParameterType::INTEGER)
                ->bind(':user', $userId, ParameterType::INTEGER)
                ->bind(':course', (int) $row->course_id, ParameterType::INTEGER)
                ->bind(':email', (string) $row->recipient_email);
            $record = $this->db->setQuery($query)->loadObject();
            if (!$record || !\Jlms\Component\Jlms\Administrator\Application\MailPreferences::enabled(
                'lesson_release',
                (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('mail_lesson_release_enabled', false),
                (string) $record->params_json
            )) { return false; }
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $start = \Jlms\Component\Jlms\Administrator\Domain\LessonAvailability::effectiveStart(
                $record->publish_up, $record->release_after_days, $record->started_at
            );
            if ($start === null || $start > $now ||
                $start <= new \DateTimeImmutable((string) $record->created_at, new \DateTimeZone('UTC')) ||
                $start <= new \DateTimeImmutable((string) $record->published_at, new \DateTimeZone('UTC')) ||
                \Jlms\Component\Jlms\Administrator\Domain\LessonAvailability::state(
                    $record->publish_up, $record->publish_down, $record->release_after_days, $record->started_at, $now
                ) !== 'available') { return false; }

            // Native Joomla category/ancestor visibility and the learner's content ACL.
            $user = new User($userId);
            $user->clearAccessRights();
            $language = preg_match('/^[a-z]{2}-[A-Z]{2}$/D', (string) $row->language) ? (string) $row->language : 'en-GB';
            $course = (new \Jlms\Component\Jlms\Administrator\Application\CatalogService($this->db))
                ->enrolledCourse($user, $language, (int) $row->course_id);
            return in_array((int) $course->content_access, array_map('intval', $user->getAuthorisedViewLevels()), true);
        } catch (\Throwable) {
            return false;
        }
    }

    /** Re-check active, incomplete enrollment immediately before SMTP. */
    private function expiryEligible(object $row): bool
    {
        try {
            $data = json_decode((string) $row->template_data, true, 16, JSON_THROW_ON_ERROR);
            $enrollmentId = is_array($data) ? (int) ($data['ENROLLMENT_ID'] ?? 0) : 0;
        } catch (\Throwable) {
            return false;
        }
        if ($enrollmentId < 1) {
            return false;
        }
        $query = $this->db->createQuery()->select('e.id')->from('#__jlms_enrollments AS e')->innerJoin('#__jlms_editions AS ed ON ed.id = e.edition_id')
            ->innerJoin('#__users AS u ON u.id = e.user_id')->where('e.id = :id')->where("e.status = 'active'")
            ->where('e.completed_at IS NULL')->where('u.block = 0')->where('LOWER(u.email) = LOWER(:email)')
            ->where('COALESCE(e.ends_at, ed.ends_at) > UTC_TIMESTAMP(6)')
            ->bind(':id', $enrollmentId, ParameterType::INTEGER)->bind(':email', (string) $row->recipient_email);
        return (bool) $this->db->setQuery($query)->loadResult();
    }

    /** Never send a stale teacher assignment notice to a removed, blocked or relinked account. */
    private function teacherAssignmentEligible(object $row): bool
    {
        $userId = (int) ($row->recipient_user_id ?? 0);
        $courseId = (int) ($row->course_id ?? 0);
        if ($userId < 1 || $courseId < 1) {
            return false;
        }
        $q = $this->db->createQuery()->select('u.id')->from('#__jlms_course_teachers AS ct')
            ->innerJoin('#__jlms_teachers AS t ON t.id = ct.teacher_id')
            ->innerJoin('#__users AS u ON u.id = t.user_id')
            ->where('ct.course_id = :course')->where('u.id = :recipient')
            ->where('u.block = 0')->where('LOWER(u.email) = LOWER(:email)')
            ->bind(':course', $courseId, ParameterType::INTEGER)
            ->bind(':recipient', $userId, ParameterType::INTEGER)
            ->bind(':email', (string) $row->recipient_email);
        return (bool) $this->db->setQuery($q)->loadResult();
    }

    /** Re-check consent revision, account, address, campaign and public visibility immediately before SMTP. */
    private function announcementEligible(object $row): bool
    {
        $userId = (int) ($row->recipient_user_id ?? 0);
        $courseId = (int) ($row->course_id ?? 0);
        if ($userId < 1 || $courseId < 1 || empty($row->recipient_consent_at)) {
            return false;
        }
        $q = $this->db->createQuery()->select('u.id')->from('#__jlms_mail_consents AS consent')
            ->innerJoin('#__users AS u ON u.id = consent.user_id')->where('consent.user_id = :user')
            ->where("consent.state = 'confirmed'")->where('consent.confirmed_at = :confirmed')
            ->where('u.block = 0')->where('LOWER(u.email) = LOWER(:email)')
            ->bind(':user', $userId, ParameterType::INTEGER)->bind(':confirmed', (string) $row->recipient_consent_at)
            ->bind(':email', (string) $row->recipient_email);
        if (!(int) $this->db->setQuery($q)->loadResult()) {
            return false;
        }
        $campaign = $this->db->createQuery()->select('course_id')->from('#__jlms_course_announcement_campaigns')
            ->where('course_id = :course')->bind(':course', $courseId, ParameterType::INTEGER);
        if (!(int) $this->db->setQuery($campaign)->loadResult()) {
            return false;
        }
        $course = $this->db->createQuery()->select('params_json')->from('#__jlms_courses')->where('id = :course')
            ->bind(':course', $courseId, ParameterType::INTEGER);
        $params = $this->db->setQuery($course)->loadResult();
        $global = (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('mail_announcement_enabled', true);
        if ($params === null || !\Jlms\Component\Jlms\Administrator\Application\MailPreferences::enabled('announcement', $global, (string) $params)) {
            return false;
        }
        $guest = new \Joomla\CMS\User\User(0);
        $guest->clearAccessRights();
        try {
            $language = preg_match('/^[a-z]{2}-[A-Z]{2}$/', (string) $row->language) ? (string) $row->language : 'en-GB';
            $catalog = new \Jlms\Component\Jlms\Administrator\Application\CatalogService($this->db);
            $public = $catalog->course($guest, $language, $courseId);
            return in_array((int) $public->content_access, array_map('intval', $guest->getAuthorisedViewLevels()), true);
        } catch (\Throwable) {
            return false;
        }
    }

}
