<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Owner history only. No lesson bodies, media, questions, answers or write operations. */
final class DashboardService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly LearningService $learning, private readonly CatalogService $catalog) {
    }

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
        if ($progress === 'completed') {
            $q->where('e.completed_at IS NOT NULL');
        } elseif ($progress === 'not_started') {
            $q->where('e.completed_at IS NULL AND e.started_at IS NULL AND e.last_activity_at IS NULL AND e.completed_total = 0');
        } elseif ($progress === 'in_progress') {
            $q->where('e.completed_at IS NULL AND (e.started_at IS NOT NULL OR e.last_activity_at IS NOT NULL OR e.completed_total > 0)');
        }
        return $q->order('e.last_activity_at DESC, e.id DESC');
    }

    /**
     * Read-only, owner-scoped summary for the private learner landing page.
     * Counts cover the entire history, rather than the paginated My Courses list.
     * Resume is offered only for a recent activity passing the existing ACL projection.
     *
     * @return array{not_started:int,in_progress:int,completed:int,latest:?array}
     */
    public function summary(User $identity, string $uiLanguage): array
    {
        $base = $this->query($identity);
        $counts = clone $base;
        $counts->clear('select')->clear('order')->select([
            "COUNT(*) AS history_total",
            "SUM(CASE WHEN e.completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_total",
            "SUM(CASE WHEN e.completed_at IS NULL AND (e.started_at IS NOT NULL OR e.last_activity_at IS NOT NULL OR e.completed_total > 0) THEN 1 ELSE 0 END) AS active_total",
        ]);
        $summary = $this->db->setQuery($counts)->loadObject();
        $total = (int) ($summary->history_total ?? 0);
        $completed = (int) ($summary->completed_total ?? 0);
        $active = (int) ($summary->active_total ?? 0);

        // Only actual learning activity is a candidate for "Continue".
        // Never infer authorisation from an enrollment's persisted status alone.
        $recentQuery = $this->query($identity, 'in_progress')
            ->where('e.last_activity_at IS NOT NULL')
            ->order('e.last_activity_at DESC, e.id DESC');
        $latest = null;
        // An inaccessible recent page must not hide an older, still usable activity.
        // Iterate the owner's history in bounded batches; each page passes through
        // the same fresh ACL/language/edition projection as My Courses.
        $offset = 0;
        do {
            $recent = $this->db->setQuery($recentQuery, $offset, 50)->loadObjectList() ?: [];
            foreach ($this->cards($identity, $uiLanguage, $recent) as $card) {
                if ($card['access'] === 'available') {
                    $latest = $card;
                    break;
                }
            }
            $offset += count($recent);
        } while ($latest === null && count($recent) === 50);

        return [
            'not_started' => max(0, $total - $completed - $active),
            'in_progress' => $active,
            'completed' => $completed,
            'latest' => $latest,
        ];
    }

    /** Read-only owner states for the currently visible catalogue page, never a global enrollment scan. */
    public function catalogStates(User $identity, string $uiLanguage, array $courseIds): array
    {
        if (!$courseIds) {
            return [];
        }
        $courseIds = array_values(array_unique(array_filter(array_map('intval', $courseIds), static fn (int $id): bool => $id > 0)));
        if (!$courseIds || count($courseIds) > 50) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        // Select exactly the latest enrollment for each requested course before
        // limiting the result set. Otherwise 50 historical editions of one course
        // can hide a different course's enrollment and display a false "Enroll" CTA.
        // The correlated lookup is scoped to the same user as the outer owner query.
        $query = $this->query($identity)
            ->whereIn('e.course_id', $courseIds)
            ->where('e.id = (SELECT MAX(latest.id) FROM #__jlms_enrollments AS latest WHERE latest.user_id = e.user_id AND latest.course_id = e.course_id)')
            ->clear('order')->order('e.id DESC');
        // At most one enrollment per requested course (50); preserve current
        // authorization and presentation checks for each resulting card.
        $rows = $this->db->setQuery($query, 0, 50)->loadObjectList();
        $cards = $this->cards($identity, $uiLanguage, $rows ?: []);
        $states = [];
        foreach ($cards as $index => $card) {
            $id = (int) $rows[$index]->course_id;
            if (!isset($states[$id])) {
                $states[$id] = $card;
            }
        }
        return $states;
    }

    public function cards(User $identity, string $uiLanguage, array $rows): array
    {
        $actor = $this->learning->freshActor($identity);
        if (count($rows) > 50) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $levels = array_map('intval', $actor->getAuthorisedViewLevels());
        $languages = LearningLanguages::available($actor, $uiLanguage);
        $groups = [];
        foreach ($rows as $row) {
            if ((int) $row->user_id !== (int) $actor->id) {
                throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            }
            $tag = $row->language === '*' ? $uiLanguage : (string) $row->language;
            if (isset($languages[$tag])) {
                $groups[$tag][] = (int) $row->course_id;
            }
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
            // Historical teaching mode belongs to the immutable enrolled version,
            // not the current catalog: keep consultation progress inapplicable
            // even when the course has been unpublished, archived or trashed.
            try {
                $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $row->rules_json);
            } catch (\DomainException) {
                $rules = null;
            }
            if (!isset($languages[$tag])) {
                $reason = 'language';
            } elseif (!isset($visible[$tag][(int) $row->course_id])) {
                $reason = 'unavailable';
            } elseif (!in_array((int) $visible[$tag][(int) $row->course_id]->content_access, $levels, true)) {
                $reason = 'access';
            } elseif (!in_array($row->status, ['active', 'completed'], true)) {
                $reason = in_array($row->status, ['suspended', 'expired', 'cancelled'], true) ? $row->status : 'pending';
            } elseif ($row->version_status !== 'published' || $row->edition_status !== 'open') {
                $reason = 'unavailable';
            } else {
                if ($rules === null) {
                    $reason = 'unavailable';
                } elseif (($row->starts_at !== null && $row->starts_at > $now) || ($row->edition_starts_at !== null && $row->edition_starts_at > $now)) {
                    $reason = 'not_open';
                } elseif (($row->ends_at !== null && $row->ends_at <= $now) || ($row->edition_ends_at !== null && $row->edition_ends_at <= $now)) {
                    $reason = 'expired';
                }
            }
            $total = (int) $row->required_total;
            $done = (int) $row->completed_total;
            $consultation = isset($rules) && is_array($rules) && $rules['mode'] === 'consultation';
            $percent = !$consultation && $total > 0 && $done <= $total ? min($row->completed_at !== null ? 100 : 99, (int) floor(100 * $done / $total)) : null;
            $cards[] = ['id' => (int) $row->id, 'title' => (string) ($row->title_snapshot ?? ''), 'learning_state' => $state,
                'access' => $reason, 'percent' => $percent, 'progress_applicable' => !$consultation, 'completed' => $row->completed_at !== null];
        }
        return $cards;
    }
}
