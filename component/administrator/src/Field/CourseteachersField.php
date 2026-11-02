<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Field;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\DatabaseInterface;
use Jlms\Component\Jlms\Administrator\Application\TeacherProfiles;

final class CourseteachersField extends \Joomla\CMS\Form\Field\ListField
{
    protected $type = 'Courseteachers';
    protected function getOptions()
    {
        $actor = new \Joomla\CMS\User\User((int) Factory::getApplication()->getIdentity()->id); $actor->clearAccessRights();
        $options = [HTMLHelper::_('select.option', '', 'JNONE')];
        $visible = [];
        foreach (TeacherProfiles::available(Factory::getContainer()->get(DatabaseInterface::class), $actor, (string) $this->form->getValue('language', null, '*')) as $row) {
            $visible[] = (int) $row->id;
            $options[] = HTMLHelper::_('select.option', (int) $row->id, $row->title . ' (#' . (int) $row->id . ')');
        }
        foreach ((array) $this->value as $selected) {
            if ((int) $selected > 0 && !in_array((int) $selected,$visible,true)) { $options[] = HTMLHelper::_('select.option',(int) $selected,\Joomla\CMS\Language\Text::_('COM_JLMS_TEACHER_UNAVAILABLE').' (#'.(int)$selected.')'); }
        }
        return array_merge(parent::getOptions(), $options);
    }
}
