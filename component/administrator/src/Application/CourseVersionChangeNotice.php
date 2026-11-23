<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue;

/**
 * MAIL-03: notify a learner only after their own enrollment is explicitly migrated
 * to a published version with substantial changes to teaching content or rules.
 */
final class CourseVersionChangeNotice
{
    public function __construct(private readonly DatabaseInterface $db) {
    }

    /** @return array{queued:int,failed:int,processed:int} */
    public function enqueueDue(int $limit = 250): array
    {
        $limit = max(1, min(500, $limit));
        $global = (bool) ComponentHelper::getParams('com_jlms')->get('mail_course_update_enabled', false);
        $override = "JSON_UNQUOTE(JSON_EXTRACT(c.params_json, '$.mail_preferences.course_update'))";
        $query = $this->db->createQuery()->select(
            'm.id AS migration_id, m.source_enrollment_id, m.target_enrollment_id, ' .
            'source.course_version_id AS source_version_id, target.course_version_id AS target_version_id, ' .
            'target.course_id, target.user_id, target.created_at AS migrated_at, ' .
            'c.title AS course_title, c.language AS course_language, c.params_json AS course_params, ' .
            'u.name AS learner_name, u.email AS learner_email, u.params AS user_params'
        )->from('#__jlms_enrollment_migrations AS m')
            ->innerJoin('#__jlms_enrollments AS source ON source.id = m.source_enrollment_id')
            ->innerJoin('#__jlms_enrollments AS target ON target.id = m.target_enrollment_id AND target.course_id = source.course_id AND target.user_id = source.user_id')
            ->innerJoin('#__jlms_courses AS c ON c.id = target.course_id')
            ->innerJoin('#__users AS u ON u.id = target.user_id')
            ->leftJoin("#__jlms_mail_queue AS notice ON notice.event_key = SHA2(CONCAT('course.version.changed:', m.id), 256)")
            ->where('notice.id IS NULL')->where("source.status = 'migrated'")
            ->where("target.status = 'active'")->where('target.completed_at IS NULL')
            ->where('u.block = 0')->where('c.state = 1')->where('c.delivery_enabled = 1')
            ->where('m.created_at > DATE_SUB(UTC_TIMESTAMP(6), INTERVAL 2 DAY)')
            ->where('m.created_at <= UTC_TIMESTAMP(6)')
            ->where('(target.starts_at IS NULL OR target.starts_at <= UTC_TIMESTAMP(6))')
            ->where('(target.ends_at IS NULL OR target.ends_at > UTC_TIMESTAMP(6))')
            ->where($global ? '(' . $override . " IS NULL OR " . $override . " <> 'disabled')"
                : $override . " = 'enabled'")
            ->order('m.id');
        $rows = $this->db->setQuery($query, 0, $limit)->loadObjectList();
        $changes = new PublicationImpact($this->db);
        $cache = [];
        $queued = 0;
$failed = 0;
        $queue = new MailQueue($this->db);
        foreach ($rows as $row) {
            try {
                $pair = (int) $row->source_version_id . ':' . (int) $row->target_version_id;
                if (!array_key_exists($pair, $cache)) {
                    $cache[$pair] = $changes->significantVersionChange(
                        (int) $row->source_version_id, (int) $row->target_version_id
                    );
                }
                if (!$cache[$pair]
                    || !MailPreferences::enabled('course_update', $global, (string) $row->course_params)
                    || !filter_var((string) $row->learner_email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $userParams = json_decode((string) $row->user_params ?: '{}', true);
                $language = is_array($userParams) && is_string($userParams['language'] ?? null)
                    ? $userParams['language'] : '';
                $language = $language ?: (string) $row->course_language;
                if (!preg_match('/^[a-z]{2}-[A-Z]{2}$/D', $language)) {
$language = 'en-GB';
                }
                $name = trim((string) $row->learner_name);
                $site = (string) Factory::getApplication()->get('sitename');
                $queue->enqueue((int) $row->course_id,
                    hash('sha256', 'course.version.changed:' . (int) $row->migration_id),
                    'com_jlms.course.updated', $language, (string) $row->learner_email, $name, [
                        'MIGRATION_ID' => (string) (int) $row->migration_id,
                        'TARGET_ENROLLMENT_ID' => (string) (int) $row->target_enrollment_id,
                        'USER_FULLNAME' => $name,
                        'USER_FULLNAME_HTML' => htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                        'COURSE_TITLE' => (string) $row->course_title,
                        'COURSE_TITLE_HTML' => htmlspecialchars((string) $row->course_title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                        'DASHBOARD_URL' => Uri::root() . 'index.php?option=com_jlms&view=dashboard',
                        'SITE_NAME' => $site,
                        'SITE_NAME_HTML' => htmlspecialchars($site, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    ], (int) $row->user_id);
                ++$queued;
            } catch (\Throwable) {
                ++$failed;
            }
        }
        return ['queued' => $queued, 'failed' => $failed, 'processed' => count($rows)];
    }
}
