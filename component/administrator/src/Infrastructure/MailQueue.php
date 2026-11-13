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
    public function enqueue(int $courseId, string $key, string $template, string $language, string $email, string $name, array $data): void
    {
        $event = match ($template) {
            'com_jlms.enrollment.confirmed' => 'enrollment',
            'com_jlms.course.completed' => 'completion',
            'com_jlms.enrollment.suspended' => 'suspension',
            'com_jlms.enrollment.reactivated' => 'reactivation',
            'com_jlms.certificate.available' => 'certificate',
            'com_jlms.course.message' => 'communication',
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
        $globalEnabled = (bool) $settings->get('mail_' . $event . '_enabled', true);
        if (!\Jlms\Component\Jlms\Administrator\Application\MailPreferences::enabled($event, $globalEnabled, (string) $courseParams)) {
            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $query = $this->db->createQuery()->insert('#__jlms_mail_queue')
            ->columns(['event_key', 'template_id', 'language', 'recipient_email', 'recipient_name', 'template_data', 'state', 'available_at', 'created_at'])
            ->values(implode(',', [
                $this->db->quote($key), $this->db->quote($template), $this->db->quote($language ?: '*'),
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
        $global = (bool) \\Joomla\\CMS\\Component\\ComponentHelper::getParams('com_jlms')->get('mail_announcement_enabled', true);
        if ($params === null || !\\Jlms\\Component\\Jlms\\Administrator\\Application\\MailPreferences::enabled('announcement', $global, (string) $params)) {
            return false;
        }
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
        } catch (\\Throwable $error) {
            $check = $this->db->createQuery()->select('id')->from('#__jlms_mail_queue')->where('event_key = :key')->bind(':key', $key);
            if ((int) $this->db->setQuery($check)->loadResult()) {
                return false;
            }
            throw $error;
        }
    }

    /** Process a bounded batch; a claimed message is never automatically sent twice. */
    public function dispatch(int $limit = 25): array
    {
        $sent = 0;
        $failed = 0;
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
            if ((string) $row->template_id === 'com_jlms.course.published' && !$this->announcementEligible($row)) {
                $skip = $this->db->createQuery()->update('#__jlms_mail_queue')->set("state = 'skipped'")
                    ->set("recipient_email = ''")->set("recipient_name = ''")->set("template_data = '{}' ")
                    ->where('id = :id')->bind(':id', $id, ParameterType::INTEGER);
                $this->db->setQuery($skip)->execute();
                continue;
            }

            try {
                $data = json_decode((string) $row->template_data, true, 32, JSON_THROW_ON_ERROR);
                $mail = new MailTemplate((string) $row->template_id, (string) $row->language);
                $mail->addRecipient((string) $row->recipient_email, (string) $row->recipient_name);
                $mail->addTemplateData($data);
                $mail->addUnsafeTags(array_keys($data));
                if (!$mail->send()) {
                    throw new \RuntimeException('Joomla mail template delivery returned false.');
                }
            } catch (\Throwable $error) {
                $attempts = (int) $row->attempts;
                $retry = $attempts < 5;
                $delay = min(3600, 60 * (2 ** min(6, $attempts - 1)));
                $update = $this->db->createQuery()->update('#__jlms_mail_queue')
                    ->set($retry ? "state = 'retry'" : "state = 'failed'")
                    ->set($retry ? 'available_at = DATE_ADD(UTC_TIMESTAMP(6), INTERVAL ' . $delay . ' SECOND)' : 'available_at = available_at')
                    ->set('last_error = :error')->set('claimed_at = NULL')->where('id = :id')
                    ->bind(':error', mb_substr($error->getMessage(), 0, 1000))
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

        return ['sent' => $sent, 'failed' => $failed, 'processed' => count($ids)];
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
        $global = (bool) \\Joomla\\CMS\\Component\\ComponentHelper::getParams('com_jlms')->get('mail_announcement_enabled', true);
        if ($params === null || !\\Jlms\\Component\\Jlms\\Administrator\\Application\\MailPreferences::enabled('announcement', $global, (string) $params)) {
            return false;
        }
        $guest = new \\Joomla\\CMS\\User\\User(0);
        $guest->clearAccessRights();
        try {
            $language = preg_match('/^[a-z]{2}-[A-Z]{2}$/', (string) $row->language) ? (string) $row->language : 'en-GB';
            $catalog = new \\Jlms\\Component\\Jlms\\Administrator\\Application\\CatalogService($this->db);
            $public = $catalog->course($guest, $language, $courseId);
            return in_array((int) $public->content_access, array_map('intval', $guest->getAuthorisedViewLevels()), true);
        } catch (\\Throwable) {
            return false;
        }
    }

}
