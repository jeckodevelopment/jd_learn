<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;

/** Extend the native category field, retaining the real record's current category. */
final class CoursecategoryField extends \Joomla\CMS\Form\Field\CategoryField
{
    public $type='Coursecategory';
    protected function getOptions()
    {
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $actor=CourseEditorAccess::fresh(Factory::getApplication()->getIdentity());
        $id=(int)$this->form->getValue('id',null,0); $current=0;
        if ($id>0) {
            $q=$db->createQuery()->select('id,catid,created_by')->from('#__jlms_courses')->where('id=:id')->bind(':id',$id,ParameterType::INTEGER);
            $course=$db->setQuery($q)->loadObject();
            if ($course && CourseEditorAccess::canEdit($actor,$course)) { $current=(int)$course->catid; }
        }
        return array_values(array_filter(parent::getOptions(),static fn($option):bool => (int)$option->value===$current || $actor->authorise('core.create','com_jlms.course.category.'.(int)$option->value)));
    }
}
