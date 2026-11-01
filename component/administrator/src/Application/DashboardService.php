<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Owner history only. No lesson bodies, media, questions, answers or write operations. */
final class DashboardService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning, private readonly CatalogService $catalog) {}

    public function query(User $identity, string $progress = 'all')
    {
        $actor = $this->learning->freshActor($identity);
        $id = (int) $actor->id;
        $q = $this->db->createQuery()->select('e.id, e.user_id, e.course_id, e.course_version_id, e.status, e.required_total, e.completed_total, e.started_at, e.completed_at, e.last_activity_at, e.starts_at, e.ends_at, v.title_snapshot, v.status AS version_status, v.rules_json, ed.status AS edition_status, ed.starts_at AS edition_starts_at, ed.ends_at AS edition_ends_at, c.language')
            ->from('#__jlms_enrollments AS e')
            ->leftJoin('#__jlms_course_versions AS v ON v.id = e.course_version_id AND v.course_id = e.course_id')
            ->leftJoin('#__jlms_editions AS ed ON ed.id = e.edition_id AND ed.course_id = e.course_id AND ed.course_version_id = e.course_version_id')
            ->leftJoin('#__jlms_courses AS c ON c.id = e.course_id')
            ->where('e.user_id = :dashboardOwner')->bind(':dashboardOwner', $id, ParameterType::INTEGER);
        if ($progress === 'completed') { $q->where('e.completed_at IS NOT NULL'); }
        elseif ($progress === 'not_started') { $q->where('e.completed_at IS NULL AND e.started_at IS NULL AND e.last_activity_at IS NULL AND e.completed_total = 0'); }
        elseif ($progress === 'in_progress') { $q->where('e.completed_at IS NULL AND (e.started_at IS NOT NULL OR e.last_activity_at IS NOT NULL OR e.completed_total > 0)'); }
        return $q->order('e.last_activity_at DESC, e.id DESC');
    }

    public function cards(User $identity, string $uiLanguage, array $rows): array
    {
        $actor = $this->learning->freshActor($identity);
        if (count($rows) > 50) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $levels = array_map('intval', $actor->getAuthorisedViewLevels());
        $languages = LearningLanguages::available($actor, $uiLanguage);
        $groups = [];
        foreach ($rows as $row) {
            if ((int) $row->user_id !== (int) $actor->id) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
            $tag = $row->language === '*' ? $uiLanguage : (string) $row->language;
            if (isset($languages[$tag])) { $groups[$tag][] = (int) $row->course_id; }
        }
        $visible = [];
        // One bounded lookup per language, never one classroom/body query per enrollment.
        foreach ($groups as $tag => $ids) {
            $query = $this->catalog->query($actor, $tag)->whereIn('c.id', array_values(array_unique($ids)));
            $visible[$tag] = $this->db->setQuery($query)->loadObjectList('id');
        }
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        $cards = [];
        foreach ($rows as $row) {
            $tag = $row->language === '*' ? $uiLanguage : (string) $row->language;
            $state = $row->completed_at !== null ? 'completed' : (($row->started_at !== null || $row->last_activity_at !== null || (int) $row->completed_total > 0) ? 'in_progress' : 'not_started');
            $reason = 'available';
            if (!isset($languages[$tag])) { $reason = 'language'; }
            elseif (!isset($visible[$tag][(int) $row->course_id])) { $reason = 'unavailable'; }
            elseif (!in_array((int) $visible[$tag][(int) $row->course_id]->content_access, $levels, true)) { $reason = 'access'; }
            elseif (!in_array($row->status, ['active', 'completed'], true)) { $reason = in_array($row->status, ['suspended', 'expired', 'cancelled'], true) ? $row->status : 'pending'; }
            elseif ($row->version_status !== 'published' || $row->edition_status !== 'open') { $reason = 'unavailable'; }
            else {
                $rules = json_decode((string) $row->rules_json, true);
                if (!is_array($rules) || ($rules['mode'] ?? '') !== 'learning' || ($rules['sequence_mode'] ?? '') !== 'free'
                    || ($rules['certificate_enabled'] ?? true) !== false || ($rules['completion_policy'] ?? '') !== 'all_required') { $reason = 'unavailable'; }
                elseif (($row->starts_at !== null && $row->starts_at > $now) || ($row->edition_starts_at !== null && $row->edition_starts_at > $now)) { $reason = 'not_open'; }
                elseif (($row->ends_at !== null && $row->ends_at <= $now) || ($row->edition_ends_at !== null && $row->edition_ends_at <= $now)) { $reason = 'expired'; }
            }
            $total = (int) $row->required_total; $done = (int) $row->completed_total;
            $percent = $total > 0 && $done <= $total ? min($row->completed_at !== null ? 100 : 99, (int) floor(100 * $done / $total)) : null;
            $cards[] = ['id' => (int) $row->id, 'title' => (string) ($row->title_snapshot ?? ''), 'learning_state' => $state,
                'access' => $reason, 'percent' => $percent, 'completed' => $row->completed_at !== null];
        }
        return $cards;
    }
}
