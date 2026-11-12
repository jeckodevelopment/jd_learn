<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Enrollments;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public object $course;
    public array $items;
    public $pagination;
    public $rosterForm;

    public function display($tpl = null)
    {
        Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        $this->course = $this->get('Course');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->rosterForm = $this->get('RosterForm');
        $this->getDocument()->getWebAssetManager()->useScript('com_jlms.rosterFilters');
        ToolbarHelper::title(Text::_('COM_JLMS_ENROLLMENTS_TITLE'), 'users');
        parent::display($tpl);
    }
}
