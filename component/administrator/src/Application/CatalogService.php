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

    public function query(User $actor, string $language, int $categoryId = 0)
    {
        if ((int) $actor->id) {
            $actor = new User((int) $actor->id);
        }
        $actor->clearAccessRights();
        $levels = $actor->block ? [0] : array_map('intval', $actor->getAuthorisedViewLevels());
        $levels = $levels ?: [0];
        $q = $this->db->createQuery()->select('c.id, c.title, c.summary, c.catid, c.content_access, c.language, c.current_version_id, c.enrollment_mode, cat.title AS category_title')
            ->from('#__jlms_courses AS c')->innerJoin('#__categories AS cat ON cat.id = c.catid')
            ->innerJoin('#__jlms_course_versions AS v ON v.id = c.current_version_id AND v.course_id = c.id')
            ->where('c.state = 1')->where('v.status = :status')->where('cat.extension = :extension')
            ->whereIn('c.catalog_access', $levels)->whereIn('cat.access', $levels)
            ->where('cat.published = 1')->where('(c.language = :allLanguage OR c.language = :language)')
            ->where('(c.publish_up IS NULL OR c.publish_up <= :nowUp)')->where('(c.publish_down IS NULL OR c.publish_down > :nowDown)');
        $status = 'published'; $extension = 'com_jlms.course'; $all = '*'; $now = gmdate('Y-m-d H:i:s');
        $q->bind(':status', $status)->bind(':extension', $extension)->bind(':allLanguage', $all)->bind(':language', $language)
            ->bind(':nowUp', $now)->bind(':nowDown', $now);
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

    public function outline(User $actor, string $language, int $id): array
    {
        $course = $this->course($actor, $language, $id);
        $version = (int) $course->current_version_id;
        $q = $this->db->createQuery()->select('id, title')->from('#__jlms_modules')
            ->where('course_version_id = :version')->bind(':version', $version, ParameterType::INTEGER)->order('ordering, id');
        $modules = $this->db->setQuery($q, 0, 1000)->loadObjectList('id');
        $q = $this->db->createQuery()->select('id, module_id, title, kind, required')->from('#__jlms_lessons')
            ->where('course_version_id = :version')->bind(':version', $version, ParameterType::INTEGER)->order('ordering, id');
        $lessons = $this->db->setQuery($q, 0, 5000)->loadObjectList();
        $grouped = [];
        foreach ($lessons as $lesson) {
            $grouped[(int) $lesson->module_id][] = $lesson;
        }
        return ['course' => $course, 'modules' => $modules, 'lessons' => $grouped];
    }
}
