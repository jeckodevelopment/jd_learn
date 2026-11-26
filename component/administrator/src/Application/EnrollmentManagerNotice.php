<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue;

/**
 * Optional operational alert for course-scoped enrollment approvers.
 * Discovery runs in the existing task, never in the applicant transaction.
 */
final class EnrollmentManagerNotice
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    /** The roster service is the authorization source for every proposed recipient. */
    public static function authorized(DatabaseInterface $db, int $courseId, int $userId): bool
    {
        if ($courseId < 1 || $userId < 1) {
            return false;
        }
        try {
            $learning = new LearningService($db, new CatalogService($db));
            (new EnrollmentRosterService($db, $learning))->course(new User($userId), $courseId);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return list<int> Distinct possible approvers, verified individually by the roster ACL. */
    private function managers(int $courseId): array
    {
        // Joomla resolves explicit/ancestor group grants, including custom admin groups.
        // These groups only discover candidates; course-scoped authorization is rechecked.
        $groups = $this->db->setQuery($this->db->createQuery()->select('id')
            ->from('#__usergroups')->order('id'), 0, 501)->loadColumn();
        if (count($groups) > 500) {
            throw new \RuntimeException('Too many Joomla user groups to discover approval managers safely.');
        }
        $grantGroups = [];
        foreach ($groups as $group) {
            if (
                Access::checkGroup((int) $group, 'jlms.enrollments.manage', 'com_jlms.course.' . $courseId)
                || Access::checkGroup((int) $group, 'core.manage', 'com_jlms')
            ) {
                $grantGroups[] = (int) $group;
            }
        }
        $ids = [];
        if ($grantGroups) {
            $q = $this->db->createQuery()->select('DISTINCT u.id')
                ->from('#__user_usergroup_map AS gm')
                ->innerJoin('#__users AS u ON u.id = gm.user_id')
                ->whereIn('gm.group_id', $grantGroups)->where('u.block = 0')->order('u.id');
            foreach ($this->db->setQuery($q, 0, 2001)->loadColumn() as $id) {
                $ids[(int) $id] = true;
            }
        }
        $q = $this->db->createQuery()->select('DISTINCT u.id')
            ->from('#__jlms_course_teachers AS ct')
            ->innerJoin('#__jlms_teachers AS t ON t.id = ct.teacher_id AND t.state = 1')
            ->innerJoin('#__users AS u ON u.id = t.user_id')
            ->where('ct.course_id = :course')->where('u.block = 0')
            ->bind(':course', $courseId, ParameterType::INTEGER);
        foreach ($this->db->setQuery($q, 0, 2001)->loadColumn() as $id) {
            $ids[(int) $id] = true;
        }
        if (count($ids) > 2000) {
            throw new \RuntimeException('Too many potential approval managers; use narrower Joomla ACL groups.');
        }
        $authorized = [];
        foreach (array_keys($ids) as $userId) {
            if (self::authorized($this->db, $courseId, $userId)) {
                $authorized[] = $userId;
            }
        }
        return $authorized;
    }

    /** @return array{queued:int,failed:int,processed:int} */
    public function enqueueDue(int $limit = 100): array
    {
        $limit = max(1, min(250, $limit));
        $global = (bool) ComponentHelper::getParams('com_jlms')->get('mail_manager_request_enabled', false);
        $override = "JSON_UNQUOTE(JSON_EXTRACT(c.params_json, '$.mail_preferences.manager_request'))";
        $query = $this->db->createQuery()->select(
            'r.id AS request_id, r.user_id AS applicant_id, r.created_at, c.id AS course_id, ' .
            'c.title AS course_title, c.language AS course_language, c.params_json AS course_params'
        )->from('#__jlms_enrollment_requests AS r')
            ->innerJoin('#__jlms_editions AS ed ON ed.id = r.edition_id')
            ->innerJoin('#__jlms_courses AS c ON c.id = ed.course_id')
            // Exclude a request from discovery once its manager fan-out has
            // been queued. Otherwise the same oldest rows fill every bounded
            // batch forever even though MailQueue correctly deduplicates them.
            ->leftJoin(("#__jlms_mail_queue AS notice ON notice.template_id = "
                . $this->db->quote('com_jlms.enrollment.manager_request') . " AND notice.event_key = "
                . "SHA2(CONCAT(" . $this->db->quote('enrollment.manager.request:') . ", r.id, "
                . $this->db->quote(':') . ", notice.recipient_user_id), 256)"))
            ->where('notice.id IS NULL')
            ->where("r.status = 'pending'")->where("ed.status = 'open'")
            ->where("c.enrollment_mode = 'approval'")->where('c.state = 1')
            ->where('c.admissions_open = 1')
            ->where('r.created_at >= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL 2 DAY)')
            ->where('r.created_at <= UTC_TIMESTAMP(6)')
            ->where($global ? '(' . $override . " IS NULL OR " . $override . " <> 'disabled')"
                : $override . " = 'enabled'")
            ->order('r.id');
        $rows = $this->db->setQuery($query, 0, $limit)->loadObjectList();
        $managers = [];
        $queue = new MailQueue($this->db);
        $queued = 0;
        $failed = 0;
        foreach ($rows as $request) {
            $courseId = (int) $request->course_id;
            try {
                if (!MailPreferences::enabled('manager_request', $global, (string) $request->course_params)) {
                    continue;
                }
                if (!array_key_exists($courseId, $managers)) {
                    $managers[$courseId] = $this->managers($courseId);
                }
                foreach ($managers[$courseId] as $userId) {
                    if ($userId === (int) $request->applicant_id) {
                        continue;
                    }
                    // Address and user language are loaded fresh; do not expose applicant PII.
                    $q = $this->db->createQuery()->select('name,email,params,block')
                        ->from('#__users')->where('id = :user')->bind(':user', $userId, ParameterType::INTEGER);
                    $recipient = $this->db->setQuery($q)->loadObject();
                    if (
                        !$recipient || (int) $recipient->block !== 0
                        || !filter_var((string) $recipient->email, FILTER_VALIDATE_EMAIL)
                    ) {
                        continue;
                    }
                    $params = json_decode((string) ($recipient->params ?: '{}'), true);
                    $language = is_array($params) ? (string) ($params['language'] ?? '') : '';
                    $language = $language ?: (string) $request->course_language;
                    if (!preg_match('/^[a-z]{2}-[A-Z]{2}$/D', $language)) {
                        $language = 'en-GB';
                    }
                    $name = trim((string) $recipient->name);
                    $courseTitle = (string) $request->course_title;
                    $siteName = (string) Factory::getApplication()->get('sitename');
                    $queue->enqueue(
                        $courseId,
                        hash('sha256', 'enrollment.manager.request:' . (int) $request->request_id . ':' . $userId),
                        'com_jlms.enrollment.manager_request',
                        $language,
                        (string) $recipient->email,
                        $name,
                        [
                            'REQUEST_ID' => (string) (int) $request->request_id,
                            'USER_FULLNAME' => $name,
                            'USER_FULLNAME_HTML' => htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            'COURSE_TITLE' => $courseTitle,
                            'COURSE_TITLE_HTML' => htmlspecialchars($courseTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            'COURSE_MANAGEMENT_URL' => Uri::root()
                                . 'administrator/index.php?option=com_jlms&view=enrollments&id=' . $courseId,
                            'SITE_NAME' => $siteName,
                            'SITE_NAME_HTML' => htmlspecialchars($siteName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                        ],
                        $userId
                    );
                    ++$queued;
                }
            } catch (\Throwable) {
                ++$failed;
            }
        }
        return ['queued' => $queued, 'failed' => $failed, 'processed' => count($rows)];
    }
}
