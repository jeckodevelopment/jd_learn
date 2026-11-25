<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Native Joomla ACL decisions shared by list, category selector and editor navigation. */
final class CourseEditorAccess
{
    public static function fresh(User $identity): User
    {
        $actor = new User((int) $identity->id);
        $actor->clearAccessRights();
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }
        return $actor;
    }
    public static function canEdit(User $actor, object $course): bool
    {
        $asset = 'com_jlms.course.' . (int)$course->id;
        return $actor->authorise('core.edit', $asset)
            || ((int)$course->created_by === (int)$actor->id && $actor->authorise('core.edit.own', $asset));
    }
    public static function canCreate(DatabaseInterface $db, User $actor, ?int $category = null): bool
    {
        if ($category === null && $actor->authorise('core.create', 'com_jlms')) {
            return true;
        }
        $q = $db->createQuery()->select('category.id')->from('#__categories AS category')
            ->where("category.extension='com_jlms.course' AND category.published<>-2")
            // Do not offer a child below a trashed ancestor even when the child is published.
            ->where('NOT EXISTS (SELECT 1 FROM #__categories AS ancestor WHERE '
                . 'ancestor.lft <= category.lft AND ancestor.rgt >= category.rgt '
                . 'AND ancestor.id <> 1 AND ancestor.published = -2)');
        if ($category !== null) {
            $q->where('category.id=:category')->bind(':category', $category, ParameterType::INTEGER);
        }
        foreach ($db->setQuery($q)->loadColumn() as $id) {
            if ($actor->authorise('core.create', 'com_jlms.course.category.' . (int)$id)) {
                return true;
            }
        }
        return false;
    }
    public static function editableIds(DatabaseInterface $db, User $actor): array
    {
        $ids = [];
        $after = 0;
        // Scan metadata in bounded batches. Permission tests stay native, including record denies.
        do {
            $q = $db->createQuery()->select('id,created_by')->from('#__jlms_courses')->where('id>:after')
                ->bind(':after', $after, ParameterType::INTEGER)->order('id');
            $rows = $db->setQuery($q, 0, 500)->loadObjectList();
            foreach ($rows as $course) {
                $after = (int)$course->id;
                if (self::canEdit($actor, $course)) {
                    $ids[] = $after;
                }
            }
        } while (count($rows) === 500);
        return $ids;
    }
}
