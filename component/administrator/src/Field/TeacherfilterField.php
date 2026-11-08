<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;
use Jlms\Component\Jlms\Administrator\Application\TeacherProfiles;

/** Lists only teachers assigned to courses the current administrator may edit. */
final class TeacherfilterField extends ListField
{
    protected $type = 'Teacherfilter';

    protected function getOptions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $actor = CourseEditorAccess::fresh(Factory::getApplication()->getIdentity());
        $courseIds = CourseEditorAccess::editableIds($db, $actor);
        $options = [HTMLHelper::_('select.option', '', 'COM_JLMS_TEACHER_FILTER_SELECT')];
        if (!$courseIds) { return array_merge(parent::getOptions(), $options); }

        $query = $db->createQuery()->select('DISTINCT teacher_id')->from('#__jlms_course_teachers')
            ->whereIn('course_id', $courseIds)->order('teacher_id');
        $ids = array_map('intval', $db->setQuery($query)->loadColumn());
        $available = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $available += TeacherProfiles::available($db, new User((int) $actor->id), '*', $chunk);
        }
        foreach ($ids as $id) {
            $label = isset($available[$id])
                ? $available[$id]->title . ' (#' . $id . ')'
                : Text::_('COM_JLMS_TEACHER_UNAVAILABLE') . ' (#' . $id . ')';
            $options[] = HTMLHelper::_('select.option', $id, $label);
        }

        return array_merge(parent::getOptions(), $options);
    }
}
