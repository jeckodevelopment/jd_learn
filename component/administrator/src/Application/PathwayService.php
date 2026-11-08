<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Read side for public learning pathways. Pathways organize courses; they
 * never create enrollments, change view levels, or unlock course content.
 */
final class PathwayService
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly LearningService $learning,
        private readonly CatalogService $catalog,
    ) {
    }

    public function query(User $identity, string $language)
    {
        $actor = $this->actor($identity);
        $levels = array_map('intval', $actor->getAuthorisedViewLevels());
        $levels = $levels ?: [0];
        $all = '*';
        $extension = 'com_jlms.pathway';
        $query = $this->db->createQuery()
            ->select('p.id, p.title, p.alias, p.description, p.language, p.access, p.catid, cat.title AS category_title')
            ->from($this->db->quoteName('#__jlms_pathways', 'p'))
            ->innerJoin($this->db->quoteName('#__categories', 'cat') . ' ON cat.id = p.catid')
            ->where('p.state = 1')
            ->where('cat.extension = :pathwayExtension')
            ->where('cat.published = 1')
            ->whereIn('p.access', $levels)
            ->whereIn('cat.access', $levels)
            ->where('(p.language = :pathwayAll OR p.language = :pathwayLanguage)')
            ->where('(cat.language = :categoryAll OR cat.language = :categoryLanguage)')
            ->bind(':pathwayExtension', $extension)
            ->bind(':pathwayAll', $all)
            ->bind(':pathwayLanguage', $language)
            ->bind(':categoryAll', $all)
            ->bind(':categoryLanguage', $language);

        $ancestor = $this->db->createQuery()->select('1')->from($this->db->quoteName('#__categories', 'ancestor'))
            ->where('ancestor.lft <= cat.lft AND ancestor.rgt >= cat.rgt AND ancestor.id <> 1')
            ->where('(ancestor.extension <> :pathwayAncestorExtension OR ancestor.published <> 1'
                . ' OR ancestor.access NOT IN (' . implode(',', $levels) . ')'
                . ' OR (ancestor.language <> :pathwayAncestorAll AND ancestor.language <> :pathwayAncestorLanguage))');

        return $query->where('NOT EXISTS (' . $ancestor . ')')
            ->bind(':pathwayAncestorExtension', $extension)
            ->bind(':pathwayAncestorAll', $all)
            ->bind(':pathwayAncestorLanguage', $language)
            ->order('cat.lft, p.title, p.id');
    }

    public function detail(User $identity, string $uiLanguage, int $pathwayId): array
    {
        $actor = $this->actor($identity);
        $pathwayQuery = $this->query($actor, $uiLanguage)
            ->where('p.id = :pathwayId')
            ->bind(':pathwayId', $pathwayId, ParameterType::INTEGER);
        $pathway = $this->db->setQuery($pathwayQuery)->loadObject()
            ?: throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);

        $courseQuery = $this->db->createQuery()
            ->select('pc.course_id, pc.ordering')
            ->from($this->db->quoteName('#__jlms_pathway_courses', 'pc'))
            ->where('pc.pathway_id = :pathwayId')
            ->bind(':pathwayId', $pathwayId, ParameterType::INTEGER)
            ->order('pc.ordering, pc.course_id');
        $relations = $this->db->setQuery($courseQuery, 0, 101)->loadObjectList();

        if (count($relations) > 100) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }

        $courseIds = array_values(array_unique(array_map(static fn (object $row): int => (int) $row->course_id, $relations)));
        $visibleCourses = [];
        $enrollments = [];
        $levels = array_map('intval', $actor->getAuthorisedViewLevels());

        if ($courseIds !== []) {
            $catalogQuery = $this->catalog->query($actor, (string) $pathway->language === '*' ? $uiLanguage : (string) $pathway->language)
                ->whereIn('c.id', $courseIds);
            $visibleCourses = $this->db->setQuery($catalogQuery, 0, 100)->loadObjectList('id');

            $enrollmentQuery = $this->db->createQuery()
                ->select('e.id, e.course_id, e.status, e.required_total, e.completed_total, e.completed_at, e.started_at, e.last_activity_at, e.starts_at, e.ends_at, v.status AS version_status, v.rules_json, ed.status AS edition_status, ed.starts_at AS edition_starts_at, ed.ends_at AS edition_ends_at')
                ->from($this->db->quoteName('#__jlms_enrollments', 'e'))
                ->leftJoin($this->db->quoteName('#__jlms_course_versions', 'v') . ' ON v.id = e.course_version_id AND v.course_id = e.course_id')
                ->leftJoin($this->db->quoteName('#__jlms_editions', 'ed') . ' ON ed.id = e.edition_id AND ed.course_id = e.course_id AND ed.course_version_id = e.course_version_id')
                ->where('e.user_id = :pathwayOwner')
                ->whereIn('e.course_id', $courseIds)
                ->order('e.last_activity_at DESC, e.id DESC')
                ->bind(':pathwayOwner', (int) $actor->id, ParameterType::INTEGER);

            foreach ($this->db->setQuery($enrollmentQuery, 0, 1000)->loadObjectList() as $enrollment) {
                $courseId = (int) $enrollment->course_id;
                $enrollments[$courseId] ??= $enrollment;
            }
        }

        $courses = [];
        foreach ($relations as $relation) {
            $courseId = (int) $relation->course_id;

            if (!isset($visibleCourses[$courseId])) {
                $courses[] = [
                    'id' => null,
                    'title' => '',
                    'access' => 'unavailable',
                    'learning_state' => 'unavailable',
                    'percent' => 0,
                ];
                continue;
            }

            $course = $visibleCourses[$courseId];
            $enrollment = $enrollments[$courseId] ?? null;
            $state = 'not_enrolled';
            $access = in_array((int) $course->content_access, $levels, true) ? 'available' : 'restricted';
            $percent = 0;

            if ($enrollment) {
                $done = (int) $enrollment->completed_total;
                $required = (int) $enrollment->required_total;
                $rules = json_decode((string) $enrollment->rules_json, true);
                $state = $enrollment->completed_at !== null
                    ? 'completed'
                    : (($enrollment->started_at !== null || $enrollment->last_activity_at !== null || $done > 0) ? 'in_progress' : 'not_started');

                $now = gmdate('Y-m-d H:i:s');
                if (($enrollment->starts_at !== null && $enrollment->starts_at > $now)
                    || ($enrollment->edition_starts_at !== null && $enrollment->edition_starts_at > $now)) {
                    $access = 'not_open';
                } elseif (($enrollment->ends_at !== null && $enrollment->ends_at <= $now)
                    || ($enrollment->edition_ends_at !== null && $enrollment->edition_ends_at <= $now)) {
                    $access = 'expired';
                } elseif (!in_array((string) $enrollment->status, ['active', 'completed'], true)
                    || $access !== 'available') {
                    $access = in_array((string) $enrollment->status, ['suspended', 'expired', 'cancelled'], true)
                        ? (string) $enrollment->status
                        : 'unavailable';
                } elseif ($enrollment->version_status !== 'published' || $enrollment->edition_status !== 'open'
                    || !is_array($rules) || ($rules['mode'] ?? '') !== 'learning'
                    || ($rules['sequence_mode'] ?? '') !== 'free' || ($rules['certificate_enabled'] ?? true) !== false
                    || ($rules['completion_policy'] ?? '') !== 'all_required') {
                    $access = 'unavailable';
                } elseif ($required > 0 && $done <= $required) {
                    $percent = $enrollment->completed_at !== null ? 100 : min(99, (int) floor(100 * $done / $required));
                }
            }

            $courses[] = [
                'id' => $courseId,
                'title' => (string) $course->title,
                'summary' => (string) $course->summary,
                'category_title' => (string) $course->category_title,
                'access' => $access,
                'learning_state' => $state,
                'percent' => $access === 'available' ? $percent : 0,
            ];
        }

        $aggregate = PathwayProgress::summarize($courses);
        // Guests may browse pathways and public course cards, but they have no
        // personal learning record to summarize.
        $hasPersonalProgress = (int) $identity->id > 0 && !$identity->guest;

        return [
            'pathway' => $pathway,
            'courses' => $courses,
            'progress' => $hasPersonalProgress ? $aggregate['percent'] : null,
            'has_unavailable_courses' => $aggregate['unavailable'],
            'required_courses' => $aggregate['required'],
        ];
    }

    private function actor(User $identity): User
    {
        if (!(int) $identity->id || $identity->guest) {
            $guest = new User();
            $guest->clearAccessRights();
            return $guest;
        }

        return $this->learning->freshActor($identity);
    }
}
