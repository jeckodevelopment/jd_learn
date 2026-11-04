<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;

final class CourseauthorsField extends \Joomla\CMS\Form\Field\ListField
{
    protected $type = 'Courseauthors';
    protected function getOptions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $actor = CourseEditorAccess::fresh(Factory::getApplication()->getIdentity());
        $ids = CourseEditorAccess::editableIds($db, $actor);
        $options = parent::getOptions();
        if (!$ids) { return $options; }
        $q = $db->createQuery()->select('DISTINCT u.id AS value,u.name AS text')->from('#__users AS u')
            ->innerJoin('#__jlms_courses AS c ON c.created_by=u.id')->whereIn('c.id', $ids)->order('u.name,u.id');
        return array_merge($options, $db->setQuery($q)->loadObjectList());
    }
}
