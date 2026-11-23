<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Jlms\Component\Jlms\Administrator\Domain\LessonAvailability;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue;

/**
 * MAIL-G: scheduled lessons which become available to an existing active enrolment.
 * Publication of a new immutable course version does not grant existing learners access
 * to that version. Hence only the learner's own version is considered.
 */
final class LessonReleaseNotice
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /** @return array{queued:int,failed:int,processed:int} */
    public function enqueueDue(int $limit = 500): array
    {
        $limit = max(1, min(1000, $limit));
        $global = (bool) ComponentHelper::getParams('com_jlms')->get('mail_lesson_release_enabled', false);
        $override = "JSON_UNQUOTE(JSON_EXTRACT(c.params_json, '$.mail_preferences.lesson_release'))";
        // Filter due releases in SQL before applying the bounded task limit. Otherwise
        // disabled or old rows could permanently starve eligible enrollments.
        $effective = "CASE WHEN l.release_after_days > 0 THEN " .
            "GREATEST(COALESCE(l.publish_up, '1000-01-01 00:00:00'), " .
            "DATE_ADD(e.started_at, INTERVAL l.release_after_days DAY)) ELSE l.publish_up END";
        $query = $this->db->createQuery()->select(
            'e.id AS enrollment_id, e.user_id, e.course_id, e.course_version_id, e.started_at, e.created_at AS enrolled_at, ' .
            'l.id AS lesson_id, l.title AS lesson_title, l.publish_up, l.publish_down, l.release_after_days, ' .
            'v.published_at, c.title AS course_title, c.language AS course_language, c.params_json AS course_params, ' .
            'u.name AS learner_name, u.email AS learner_email, u.params AS learner_params'
        )->from('#__jlms_enrollments AS e')
            ->innerJoin('#__jlms_lessons AS l ON l.course_version_id = e.course_version_id')
            ->innerJoin('#__jlms_course_versions AS v ON v.id = e.course_version_id AND v.course_id = e.course_id')
            ->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')
            ->innerJoin('#__jlms_editions AS ed ON ed.id = e.edition_id AND ed.course_version_id = e.course_version_id')
            ->innerJoin('#__users AS u ON u.id = e.user_id')
            ->leftJoin('#__jlms_mail_queue AS notice ON notice.event_key = SHA2(CONCAT(\'lesson.available:\', e.id, \':\', l.id), 256)')
            ->where('notice.id IS NULL')->where("v.status = 'published'")
            ->where("ed.status = 'open'")->where("e.status = 'active'")
            ->where('e.completed_at IS NULL')->where('u.block = 0')
            ->where('c.state = 1')->where('c.delivery_enabled = 1')
            ->where('(e.starts_at IS NULL OR e.starts_at <= UTC_TIMESTAMP(6))')
            ->where('(e.ends_at IS NULL OR e.ends_at > UTC_TIMESTAMP(6))')
            ->where('(ed.starts_at IS NULL OR ed.starts_at <= UTC_TIMESTAMP(6))')
            ->where('(ed.ends_at IS NULL OR ed.ends_at > UTC_TIMESTAMP(6))')
            ->where('(l.release_after_days IS NULL OR l.release_after_days = 0 OR e.started_at IS NOT NULL)')
            ->where('(' . $effective . ') > DATE_SUB(UTC_TIMESTAMP(6), INTERVAL 2 DAY)')
            ->where('(' . $effective . ') <= UTC_TIMESTAMP(6)')
            ->where('(' . $effective . ') > e.created_at')
            ->where('(' . $effective . ') > v.published_at')
            ->where('(l.publish_down IS NULL OR l.publish_down > UTC_TIMESTAMP(6))')
            ->where($global ? '(' . $override . " IS NULL OR " . $override . " <> 'disabled')"
                : $override . " = 'enabled'")
            ->order('e.id, l.id');
        $rows = $this->db->setQuery($query, 0, $limit)->loadObjectList();
        $queue = new MailQueue($this->db);
        $queued = 0;
        $failed = 0;
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $window = $now->modify('-2 days');
        foreach ($rows as $row) {
            if (!MailPreferences::enabled('lesson_release', $global, (string) $row->course_params)) {
                continue;
            }
            try {
                $effective = LessonAvailability::effectiveStart($row->publish_up, $row->release_after_days, $row
                    ->started_at);
                if (
                    $effective === null || $effective > $now || $effective <= $window ||
                    $effective <= new \DateTimeImmutable((string) $row->enrolled_at, new \DateTimeZone('UTC')) ||
                    $effective <= new \DateTimeImmutable((string) $row->published_at, new \DateTimeZone('UTC')) ||
                    LessonAvailability::state($row->publish_up, $row->publish_down, $row
                        ->release_after_days, $row->started_at, $now) !== 'available' ||
                    !filter_var((string) $row->learner_email, FILTER_VALIDATE_EMAIL)
                ) {
                    continue;
                }
                $params = json_decode((string) $row->learner_params ?: '{}', true);
                $language = is_array($params) && is_string($params['language'] ?? null) ? $params['language'] : '';
                $language = $language ?: (string) $row->course_language;
                if (!preg_match('/^[a-z]{2}-[A-Z]{2}$/D', $language)) {
                    $language = 'en-GB';
                }
                $name = trim((string) $row->learner_name);
                $queue->enqueue(
                    (int) $row->course_id,
                    hash('sha256', 'lesson.available:' . (int) $row->enrollment_id . ':' . (int) $row->lesson_id),
                    'com_jlms.lesson.available',
                    $language,
                    (string) $row->learner_email,
                    $name,
                    [
                        'ENROLLMENT_ID' => (string) (int) $row->enrollment_id,
                        'LESSON_ID' => (string) (int) $row->lesson_id,
                        'USER_FULLNAME' => $name,
                        'COURSE_TITLE' => (string) $row->course_title,
                        'LESSON_TITLE' => (string) $row->lesson_title,
                        'USER_FULLNAME_HTML' => htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                        'LESSON_TITLE_HTML' => htmlspecialchars((string) $row
                            ->lesson_title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                        'COURSE_TITLE_HTML' => htmlspecialchars((string) $row
                            ->course_title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                        'DASHBOARD_URL' => Uri::root() . 'index.php?option=com_jlms&view=dashboard',
                        'SITE_NAME' => (string) Factory::getApplication()->get('sitename'),
                        'SITE_NAME_HTML' => htmlspecialchars((string) Factory::getApplication()
                            ->get('sitename'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    ],
                    (int) $row->user_id
                );
                ++$queued;
            } catch (\Throwable) {
                ++$failed;
            }
        }
        return ['queued' => $queued, 'failed' => $failed, 'processed' => count($rows)];
    }
}
