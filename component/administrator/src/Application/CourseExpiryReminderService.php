<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue;

/** Queues one optional operational reminder for active learners before an enrolment deadline. */
final class CourseExpiryReminderService
{
    public function __construct(private readonly DatabaseInterface $db) {}

    /** @return array{queued:int,failed:int,processed:int} */
    public function enqueueDue(int $limit = 5000): array
    {
        $params = ComponentHelper::getParams('com_jlms');
        if (!(bool) $params->get('mail_expiry_enabled', false)) {
            return ['queued' => 0, 'failed' => 0, 'processed' => 0];
        }
        $days = max(1, min(30, (int) $params->get('mail_expiry_days', 3)));
        $until = 'DATE_ADD(UTC_TIMESTAMP(6), INTERVAL ' . $days . ' DAY)';
        $query = $this->db->createQuery()->select('e.id AS enrollment_id, e.course_id, e.ends_at AS enrollment_ends_at,
                ed.ends_at AS edition_ends_at, u.name AS learner_name, u.email AS learner_email, u.params AS user_params,
                c.title AS course_title, c.language AS course_language, c.params_json AS course_params')
            ->from('#__jlms_enrollments AS e')->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')
            ->innerJoin('#__jlms_editions AS ed ON ed.id = e.edition_id')
            ->innerJoin('#__users AS u ON u.id = e.user_id')
            ->where('u.block = 0')->where("e.status = 'active'")->where('e.completed_at IS NULL')
            ->where('(e.ends_at IS NOT NULL OR ed.ends_at IS NOT NULL)')
            ->where('COALESCE(e.ends_at, ed.ends_at) > UTC_TIMESTAMP(6)')
            ->where('COALESCE(e.ends_at, ed.ends_at) <= ' . $until)
            ->order('COALESCE(e.ends_at, ed.ends_at), e.id');
        $limit = max(1, min(5000, $limit));
        $rows = $this->db->setQuery($query, 0, $limit)->loadObjectList();
        $queue = new MailQueue($this->db);
        $queued = 0;
        $failed = 0;
        foreach ($rows as $row) {
            if (!MailPreferences::enabled('expiry', true, (string) $row->course_params)) {
                continue;
            }
            $end = (string) ($row->enrollment_ends_at ?: $row->edition_ends_at);
            $name = trim((string) $row->learner_name);
            $userParams = json_decode((string) $row->user_params, true);
            $language = is_array($userParams) && is_string($userParams['language'] ?? null) ? $userParams['language'] : '';
            try {
                $queue->enqueue((int) $row->course_id, hash('sha256', 'course.expiring:' . (int) $row->enrollment_id . ':' . $end),
                    'com_jlms.course.expiring', (string) ($language ?: $row->course_language),
                    (string) $row->learner_email, $name, [
                        'ENROLLMENT_ID' => (string) (int) $row->enrollment_id,
                        'NAME' => $name,
                        'COURSE' => (string) $row->course_title,
                        'COURSE_ENDS_AT' => gmdate('Y-m-d H:i', strtotime($end . ' UTC')) . ' UTC',
                        'COURSE_URL' => \Joomla\CMS\Uri\Uri::root() . 'index.php?option=com_jlms&view=dashboard',
                        'SITENAME' => (string) \Joomla\CMS\Factory::getApplication()->get('sitename'),
                    ]);
                $queued++;
            } catch (\Throwable) {
                $failed++;
            }
        }
        return ['queued' => $queued, 'failed' => $failed, 'processed' => count($rows)];
    }
}
