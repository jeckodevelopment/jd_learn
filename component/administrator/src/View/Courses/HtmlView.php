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

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        ToolbarHelper::title('JD Learn', 'graduation-cap');
        if (Factory::getApplication()->getIdentity()->authorise('core.create', 'com_jlms')) {
            ToolbarHelper::addNew('course.add');
        }
        parent::display($tpl);
    }
}
