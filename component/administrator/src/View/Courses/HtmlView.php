<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\View\Courses;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;

final class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null)
    {
        $actor=\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh(Factory::getApplication()->getIdentity());
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        ToolbarHelper::title(\Joomla\CMS\Language\Text::_('COM_JLMS_COURSES'), 'graduation-cap');
        if (\Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canCreate(Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class),$actor)) {
            ToolbarHelper::addNew('course.add');
        }
        if ($this->items) {
            ToolbarHelper::editList('course.edit');
            ToolbarHelper::custom('courses.programme', 'list', 'list', 'COM_JLMS_CURRICULUM', true);
        }
        if ($actor->authorise('core.options','com_jlms') || $actor->authorise('core.admin','com_jlms')) { ToolbarHelper::preferences('com_jlms'); }
        parent::display($tpl);
    }
}
