<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;

final class CoursefiltercategoryField extends \Joomla\CMS\Form\Field\CategoryField
{
    public $type = 'Coursefiltercategory';
    protected function getOptions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $actor = CourseEditorAccess::fresh(Factory::getApplication()->getIdentity());
        $ids = CourseEditorAccess::editableIds($db, $actor);
        $categories = [];
        if ($ids) {
            $q = $db->createQuery()->select('DISTINCT catid')->from('#__jlms_courses')->whereIn('id', $ids);
            $categories = array_map('intval', $db->setQuery($q)->loadColumn());
        }
        return array_values(array_filter(parent::getOptions(), static fn($option): bool => (string) $option->value === '' || in_array((int) $option->value, $categories, true)));
    }
}
