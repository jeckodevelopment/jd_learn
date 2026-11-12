<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Promotional data only. This service never selects lesson bodies, media or personal data. */
final class CatalogService
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function query(User $actor, string $language, int $categoryId = 0, int $tagId = 0, int $teacherId = 0, int $pathwayId = 0)
    {
        $query = $this->courseQuery($actor, $language, $categoryId, false);
        $filterActor = (int) $actor->id ? new User((int) $actor->id) : $actor;
        $filterActor->clearAccessRights();
        $levels = $filterActor->block ? [0] : array_map('intval', $filterActor->getAuthorisedViewLevels());
        $levels = $levels ?: [0];
        if ($tagId > 0) {
            $courseTagAlias = 'com_jlms.course';
            $tagActor = (int) $actor->id ? new User((int) $actor->id) : $actor;
            $tagActor->clearAccessRights();
            $levels = $tagActor->block ? [0] : array_map('intval', $tagActor->getAuthorisedViewLevels());
            $levels = $levels ?: [0];
            $tagMap = $this->db->createQuery()->select('1')->from('#__contentitem_tag_map AS tag_map')
                ->innerJoin('#__tags AS tag ON tag.id = tag_map.tag_id')
                ->where('tag_map.content_item_id = c.id')->where('tag_map.type_alias = :courseTagAlias')
                ->where('tag.id = :catalogTagId')->where('tag.published = 1')->whereIn('tag.access', $levels);
            $query->where('EXISTS (' . $tagMap . ')')
                ->bind(':courseTagAlias', $courseTagAlias)
                ->bind(':catalogTagId', $tagId, ParameterType::INTEGER);
        }
        if ($teacherId > 0) {
            $teacher = $teacherId;
            $assigned = $this->db->createQuery()->select('1')->from('#__jlms_course_teachers AS catalog_teacher')
                ->where('catalog_teacher.course_id = c.id')->where('catalog_teacher.teacher_id = :catalogTeacherId');
            $query->where('EXISTS (' . $assigned . ')')->bind(':catalogTeacherId', $teacher, ParameterType::INTEGER);
        } elseif ($teacherId < 0) {
            $query->where('1 = 0');
        }
        if ($pathwayId > 0) {
            $pathway = $pathwayId;
            $assigned = $this->db->createQuery()->select('1')->from('#__jlms_pathway_courses AS catalog_pathway_course')
                ->where('catalog_pathway_course.course_id = c.id')->where('catalog_pathway_course.pathway_id = :catalogPathwayId')
                ->where('EXISTS (SELECT 1 FROM #__jlms_pathways AS visible_pathway INNER JOIN #__categories AS visible_pathway_cat ON visible_pathway_cat.id = visible_pathway.catid'
                    . ' WHERE visible_pathway.id = catalog_pathway_course.pathway_id AND visible_pathway.state = 1'
                    . ' AND visible_pathway.access IN (' . implode(',', $levels) . ') AND visible_pathway_cat.published = 1'
                    . ' AND visible_pathway_cat.access IN (' . implode(',', $levels) . ')'
                    . ' AND (visible_pathway.language = :catalogPathwayAll OR visible_pathway.language = :catalogPathwayLanguage)'
                    . ' AND (visible_pathway_cat.language = :catalogPathwayCatAll OR visible_pathway_cat.language = :catalogPathwayCatLanguage)'
                    . ' AND NOT EXISTS (SELECT 1 FROM #__categories AS vp_ancestor WHERE vp_ancestor.lft <= visible_pathway_cat.lft AND vp_ancestor.rgt >= visible_pathway_cat.rgt AND vp_ancestor.id <> 1'
                    . ' AND (vp_ancestor.extension <> :catalogPathwayExtension OR vp_ancestor.published <> 1 OR vp_ancestor.access NOT IN (' . implode(',', $levels) . ')'
                    . ' OR (vp_ancestor.language <> :catalogPathwayAncestorAll AND vp_ancestor.language <> :catalogPathwayAncestorLanguage))))');
            $all = '*'; $extension = 'com_jlms.pathway';
            $query->where('EXISTS (' . $assigned . ')')->bind(':catalogPathwayId', $pathway, ParameterType::INTEGER)
                ->bind(':catalogPathwayAll', $all)->bind(':catalogPathwayLanguage', $language)
                ->bind(':catalogPathwayCatAll', $all)->bind(':catalogPathwayCatLanguage', $language)
                ->bind(':catalogPathwayExtension', $extension)->bind(':catalogPathwayAncestorAll', $all)->bind(':catalogPathwayAncestorLanguage', $language);
        } elseif ($pathwayId < 0) {
            $query->where('1 = 0');
        }
        return $query;
    }

    /** Published teacher choices attached to courses visible in this catalog scope. */
    public function publicTeachers(User $actor, string $language, int $categoryId = 0): array
    {
        $query = $this->query($actor, $language, $categoryId)->clear('select')->select('DISTINCT catalog_teacher.teacher_id')
            ->innerJoin('#__jlms_course_teachers AS catalog_teacher ON catalog_teacher.course_id = c.id');
        $ids = array_map('intval', $this->db->setQuery($query, 0, 2001)->loadColumn());
        if (count($ids) > 2000) { $ids = array_slice($ids, 0, 2000); }
        return TeacherProfiles::available($this->db, $actor, $language, $ids);
    }

    /** Visible pathways related to one or more courses in this catalog scope. */
    public function publicPathwayIds(User $actor, string $language, int $categoryId = 0): array
    {
        $query = $this->query($actor, $language, $categoryId)->clear('select')->select('DISTINCT catalog_pathway_course.pathway_id')
            ->innerJoin('#__jlms_pathway_courses AS catalog_pathway_course ON catalog_pathway_course.course_id = c.id');
        return array_map('intval', $this->db->setQuery($query, 0, 2001)->loadColumn());
    }

    /** Returns published tags attached to courses already visible to this actor. */
    public function publicTags(User $actor, string $language, int $categoryId = 0): array
    {
        $tagActor = (int) $actor->id ? new User((int) $actor->id) : $actor;
        $tagActor->clearAccessRights();
        $levels = $tagActor->block ? [0] : array_map('intval', $tagActor->getAuthorisedViewLevels());
        $levels = $levels ?: [0];
        $courseTagAlias = 'com_jlms.course';
        $query = $this->courseQuery($actor, $language, $categoryId, false)
            ->clear('select')->select('DISTINCT tag.id, tag.title')
            ->innerJoin('#__contentitem_tag_map AS tag_map ON tag_map.content_item_id = c.id AND tag_map.type_alias = :courseTagAlias')
            ->innerJoin('#__tags AS tag ON tag.id = tag_map.tag_id')
            ->where('tag.published = 1')->whereIn('tag.access', $levels)
            ->order('tag.title, tag.id')
            ->bind(':courseTagAlias', $courseTagAlias);
        return $this->db->setQuery($query, 0, 500)->loadObjectList();
    }

    /** Simulates catalogue/content access for selected Joomla view levels without changing identity or memberships. */
    public function accessPreview(User $identity, int $courseId, string $language, array $requestedLevels = []): array
    {
        $actor = new User((int) $identity->id);
        $actor->clearAccessRights();
        $extension = 'com_jlms.course';
        $q = $this->db->createQuery()->select('c.id, c.title, c.created_by, c.catid, c.state, c.catalog_access, c.content_access, c.language, c.current_version_id, c.admissions_open, c.delivery_enabled, c.publish_up, c.publish_down, cat.lft, cat.rgt, cat.published AS category_published, cat.access AS category_access, cat.language AS category_language')
            ->from('#__jlms_courses AS c')->innerJoin('#__categories AS cat ON cat.id = c.catid AND cat.extension = :extension')
            ->where('c.id = :course')->bind(':course', $courseId, ParameterType::INTEGER)->bind(':extension', $extension);
        $course = $this->db->setQuery($q)->loadObject() ?: throw new \DomainException('COM_JLMS_RESOURCE_NOT_FOUND');
        $asset = 'com_jlms.course.' . (int) $course->id;
        $own = (int) $course->created_by === (int) $actor->id && $actor->authorise('core.edit.own', $asset);
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')
            || (!$actor->authorise('core.edit', $asset) && !$own)) {
            throw new \DomainException('JERROR_ALERTNOAUTHOR');
        }
        $available = $this->db->setQuery($this->db->createQuery()->select('id, title')->from('#__viewlevels')->order('ordering, id'))->loadObjectList();
        $availableIds = array_map(static fn (object $row): int => (int) $row->id, $available);
        $selected = array_values(array_intersect($availableIds, array_unique(array_map('intval', $requestedLevels))));
        if (!$selected) { $selected = [in_array(1, $availableIds, true) ? 1 : ($availableIds[0] ?? 0)]; }
        $selected = array_values(array_filter($selected));
        $left = (int) $course->lft;
        $right = (int) $course->rgt;
        $ancestorsQuery = $this->db->createQuery()->select('id, published, access, language')->from('#__categories')
            ->where('lft <= :left')->where('rgt >= :right')->where('id <> 1')
            ->bind(':left', $left, ParameterType::INTEGER)->bind(':right', $right, ParameterType::INTEGER)
            ->order('lft');
        $ancestors = $this->db->setQuery($ancestorsQuery)->loadObjectList();
        $categoryVisible = (int) $course->category_published === 1
            && in_array((int) $course->category_access, $selected, true)
            && ((string) $course->category_language === '*' || (string) $course->category_language === $language);
        foreach ($ancestors as $category) {
            if ((int) $category->published !== 1 || !in_array((int) $category->access, $selected, true)
                || !in_array((string) $category->language, ['*', $language], true)) { $categoryVisible = false; break; }
        }
        $now = time();
        $courseLanguage = in_array((string) $course->language, ['*', $language], true);
        $catalogVisible = (int) $course->state === 1 && $course->current_version_id !== null
            && in_array((int) $course->catalog_access, $selected, true) && $categoryVisible && $courseLanguage
            && (!$course->publish_up || strtotime((string) $course->publish_up) <= $now)
            && (!$course->publish_down || strtotime((string) $course->publish_down) > $now);
        $contentAllowed = in_array((int) $course->content_access, $selected, true);
        return ['levels' => $available, 'selected' => $selected, 'catalog_visible' => $catalogVisible,
            'content_allowed' => $contentAllowed, 'access_requirements_met' => $catalogVisible && $contentAllowed];
    }

    /** Availability for an existing enrollment is independent of catalog publication. */
    private function courseQuery(User $actor, string $language, int $categoryId, bool $forEnrolledLearner)
    {
        if ((int) $actor->id) {
            $actor = new User((int) $actor->id);
        }
        $actor->clearAccessRights();
        $levels = $actor->block ? [0] : array_map('intval', $actor->getAuthorisedViewLevels());
        $levels = $levels ?: [0];
        $q = $this->db->createQuery()->select('c.id, c.title, c.summary, c.catid, c.content_access, c.language, c.current_version_id, c.enrollment_mode, c.admissions_open, c.delivery_enabled, cat.title AS category_title')
            ->from('#__jlms_courses AS c')->innerJoin('#__categories AS cat ON cat.id = c.catid')
            ->innerJoin('#__jlms_course_versions AS v ON v.id = c.current_version_id AND v.course_id = c.id')
            ->where('v.status = :status')->where('cat.extension = :extension')
            ->whereIn('cat.access', $levels)
            ->where('cat.published = 1')->where('(c.language = :allLanguage OR c.language = :language)');
        if (!$forEnrolledLearner) {
            $q->where('c.state = 1')->whereIn('c.catalog_access', $levels)
                ->where('(c.publish_up IS NULL OR c.publish_up <= :nowUp)')
                ->where('(c.publish_down IS NULL OR c.publish_down > :nowDown)');
        }
        $status = 'published'; $extension = 'com_jlms.course'; $all = '*';
        $q->bind(':status', $status)->bind(':extension', $extension)->bind(':allLanguage', $all)->bind(':language', $language);
        if (!$forEnrolledLearner) {
            $now = gmdate('Y-m-d H:i:s');
            $q->bind(':nowUp', $now)->bind(':nowDown', $now);
        }
        // An unpublished/restricted ancestor hides the descendant even through a direct course URL.
        $ancestor = $this->db->createQuery()->select('1')->from('#__categories AS ancestor')
            ->where('ancestor.lft <= cat.lft AND ancestor.rgt >= cat.rgt AND ancestor.id <> 1')
            ->where('(ancestor.published <> 1 OR ancestor.access NOT IN (' . implode(',', $levels) . ')'
                . ' OR (ancestor.language <> :ancestorAll AND ancestor.language <> :ancestorLanguage))');
        $q->where('NOT EXISTS (' . $ancestor . ')')->bind(':ancestorAll', $all)->bind(':ancestorLanguage', $language);
        if ($categoryId > 0) {
            $filter = $this->db->createQuery()->select('1')->from('#__categories AS selected')
                ->where('selected.id = :category')->where('selected.lft <= cat.lft AND selected.rgt >= cat.rgt');
            $q->where('EXISTS (' . $filter . ')')->bind(':category', $categoryId, ParameterType::INTEGER);
        }
        return $q;
    }

    public function course(User $actor, string $language, int $id): object
    {
        $q = $this->query($actor, $language)->where('c.id = :course')->bind(':course', $id, ParameterType::INTEGER);
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
    }

    /** A current enrollment can outlive catalog visibility, but not taxonomy or content ACL. */
    public function enrolledCourse(User $actor, string $language, int $id): object
    {
        $q = $this->courseQuery($actor, $language, 0, true)->where('c.id = :course')->bind(':course', $id, ParameterType::INTEGER);
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
    }

    /** Native taxonomy only; never expose child counts or teaching records. */
    public function categoryQuery(User $actor, string $language)
    {
        if ((int) $actor->id) { $actor = new User((int) $actor->id); }
        $actor->clearAccessRights();
        $levels = $actor->block ? [0] : array_map('intval', $actor->getAuthorisedViewLevels());
        $levels = $levels ?: [0];
        $extension = 'com_jlms.course'; $all = '*';
        $q = $this->db->createQuery()->select('cat.id, cat.parent_id, cat.title, cat.alias, cat.description, cat.language, cat.lft, cat.rgt')
            ->from('#__categories AS cat')->where('cat.id > 1')->where('cat.extension = :categoryExtension')
            ->where('cat.published = 1')->whereIn('cat.access', $levels)
            ->where('(cat.language = :categoryAll OR cat.language = :categoryLanguage)')
            ->bind(':categoryExtension', $extension)->bind(':categoryAll', $all)->bind(':categoryLanguage', $language);
        $ancestor = $this->db->createQuery()->select('1')->from('#__categories AS ancestor')
            ->where('ancestor.lft <= cat.lft AND ancestor.rgt >= cat.rgt AND ancestor.id <> 1')
            ->where('(ancestor.extension <> :ancestorExtension OR ancestor.published <> 1 OR ancestor.access NOT IN (' . implode(',', $levels) . ')'
                . ' OR (ancestor.language <> :categoryAncestorAll AND ancestor.language <> :categoryAncestorLanguage))');
        return $q->where('NOT EXISTS (' . $ancestor . ')')->bind(':ancestorExtension', $extension)
            ->bind(':categoryAncestorAll', $all)->bind(':categoryAncestorLanguage', $language);
    }

    public function category(User $actor, string $language, int $id): object
    {
        $q = $this->categoryQuery($actor, $language)->where('cat.id = :categoryId')->bind(':categoryId', $id, ParameterType::INTEGER);
        return $this->db->setQuery($q)->loadObject() ?: throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
    }

    public function categoryPath(User $actor, string $language, int $id): array
    {
        $category = $this->category($actor, $language, $id);
        $left = (int) $category->lft; $right = (int) $category->rgt;
        $q = $this->categoryQuery($actor, $language)->where('cat.lft <= :pathLeft')->where('cat.rgt >= :pathRight')
            ->bind(':pathLeft', $left, ParameterType::INTEGER)->bind(':pathRight', $right, ParameterType::INTEGER)->order('cat.lft');
        $items = $this->db->setQuery($q, 0, 101)->loadObjectList();
        if (count($items) > 100) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return $items;
    }

    public function outline(User $actor, string $language, int $id): array
    {
        $course = $this->course($actor, $language, $id);
        $version = (int) $course->current_version_id;
        $rulesQuery = $this->db->createQuery()->select('rules_json')->from('#__jlms_course_versions')
            ->where('id = :previewVersion')->bind(':previewVersion', $version, ParameterType::INTEGER);
        $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $this->db->setQuery($rulesQuery)->loadResult());
        $q = $this->db->createQuery()->select('id, title')->from('#__jlms_modules')
            ->where('course_version_id = :version')->bind(':version', $version, ParameterType::INTEGER)->order('ordering, id');
        $modules = $this->db->setQuery($q, 0, 1000)->loadObjectList('id');
        $q = $this->db->createQuery()->select('id, module_id, title, kind, required, preview')->from('#__jlms_lessons')
            ->where('course_version_id = :version')->bind(':version', $version, ParameterType::INTEGER)->order('ordering, id');
        $lessons = $this->db->setQuery($q, 0, 5000)->loadObjectList();
        $grouped = [];
        foreach ($lessons as $lesson) {
            $grouped[(int) $lesson->module_id][] = $lesson;
        }
        $query = $this->db->createQuery()->select('*')->from('#__jlms_courses')
            ->where('id = :course')->bind(':course', $id, ParameterType::INTEGER);
        $editorial = $this->db->setQuery($query)->loadObject();
        $snapshot = CoursePresentation::snapshot($editorial);
        $presentation = CoursePresentation::fromSnapshot($snapshot);
        $calendar = CourseSchedule::fromSnapshot($snapshot);
        $teachers = TeacherProfiles::current($this->db, $actor, $language, $id);
        $q=$this->db->createQuery()->select('e.status,e.starts_at,e.ends_at')->from('#__jlms_editions AS e')
            ->innerJoin('#__jlms_course_versions AS v ON v.id=e.course_version_id AND v.course_id=e.course_id')
            ->where('e.course_id=:windowCourse')->where('e.course_version_id=:windowVersion')->where("e.code=CONCAT('default-v',v.version_no)")
            ->bind(':windowCourse',$id,ParameterType::INTEGER)->bind(':windowVersion',$version,ParameterType::INTEGER);
        $edition=$this->db->setQuery($q)->loadObject();
        $startWindow=$edition && $edition->status==='open' && CourseSchedule::within($edition->starts_at,$edition->ends_at);
        return ['course' => $course, 'modules' => $modules, 'lessons' => $grouped, 'public_previews' => $rules['public_previews'], 'presentation' => $presentation, 'teachers' => $teachers, 'calendar'=>$calendar, 'start_window_open'=>$startWindow];
    }

    /** Return only an explicitly previewable text lesson from the current public course version. */
    public function publicLessonPreview(User $actor, string $language, int $courseId, int $lessonId): object
    {
        $course = $this->course($actor, $language, $courseId);
        $versionId = (int) $course->current_version_id;
        $rulesQuery = $this->db->createQuery()->select('rules_json')->from('#__jlms_course_versions')
            ->where('id = :previewVersion')->bind(':previewVersion', $versionId, ParameterType::INTEGER);
        $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $this->db->setQuery($rulesQuery)->loadResult());
        if (!$rules['public_previews']) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        $query = $this->db->createQuery()->select('l.id, l.title, l.body, l.publish_up, l.publish_down, l.release_after_days')
            ->from('#__jlms_lessons AS l')->innerJoin('#__jlms_modules AS m ON m.id = l.module_id AND m.course_version_id = l.course_version_id')
            ->where('l.id = :previewLesson')->where('l.course_version_id = :previewVersion')
            ->where('l.kind = :previewKind')->where('l.preview = 1')
            ->bind(':previewLesson', $lessonId, ParameterType::INTEGER)->bind(':previewVersion', $versionId, ParameterType::INTEGER);
        $previewKind = 'text';
        $query
            ->bind(':previewKind', $previewKind);
        $lesson = $this->db->setQuery($query)->loadObject();
        if (!$lesson || \Jlms\Component\Jlms\Administrator\Domain\LessonAvailability::state(
            $lesson->publish_up, $lesson->publish_down, $lesson->release_after_days) !== 'available') {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return $lesson;
    }
}
