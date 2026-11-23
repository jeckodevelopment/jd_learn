<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\View\Teachers;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

final class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null)
    {
        $actor = new \Joomla\CMS\User\User((int) Factory::getApplication()->getIdentity()->id);
        $actor->clearAccessRights();
        if (!$actor->id || $actor->block || !$actor->authorise('core.manage', 'com_jlms')) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        ToolbarHelper::title(Text::_('COM_JLMS_TEACHERS'), 'users');
        if ($actor->authorise('core.create', 'com_jlms') && $actor->authorise('jlms.teachers.manage', 'com_jlms')) {
            ToolbarHelper::addNew('teacher.add');
        }
        if ($actor->authorise('jlms.teachers.manage', 'com_jlms')
            && $actor->authorise('core.edit.state', 'com_jlms')) {
            if ((string) $this->state->get('filter.published') === '-2') {
                ToolbarHelper::custom('teacher.restore', 'refresh', 'refresh', 'JTOOLBAR_RESTORE', true);
                if ($actor->authorise('core.delete', 'com_jlms')) {
                    ToolbarHelper::deleteList('COM_JLMS_TEACHER_PURGE_CONFIRM', 'teacher.purge');
                }
            } else {
                ToolbarHelper::publish('teacher.publish', 'JTOOLBAR_PUBLISH', true);
                ToolbarHelper::unpublish('teacher.unpublish', 'JTOOLBAR_UNPUBLISH', true);
                ToolbarHelper::trash('teacher.trash');
            }
        }
        ToolbarHelper::help('teachers', true);
        parent::display($tpl);
    }
}
