<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Teacher;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends \Joomla\CMS\MVC\View\HtmlView
{
    public $item;
    public $form;
    public function display($tpl = null)
    {
        $actor = Factory::getApplication()->getIdentity();
        if (!$actor->authorise('jlms.teachers.manage', 'com_jlms')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
        $this->item = $this->get('Item'); $id = (int) $this->item->id;
        $scope = $id ? 'com_jlms.teacher.' . $id : 'com_jlms';
        if ($id ? (!$actor->authorise('core.edit', $scope) && !((int) $this->item->created_by === (int) $actor->id && $actor->authorise('core.edit.own', $scope))) : !$actor->authorise('core.create', $scope)) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
        $this->form = $this->get('Form');
        if (!$this->form) { throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'),500); }
        if (!$actor->authorise('core.edit.state', $scope)) { $this->form->setFieldAttribute('state','disabled','true'); }
        ToolbarHelper::title(Text::_('COM_JLMS_TEACHER'), 'user');
        ToolbarHelper::apply('teacher.apply'); ToolbarHelper::save('teacher.save'); ToolbarHelper::cancel('teacher.cancel');
        parent::display($tpl);
    }
}
