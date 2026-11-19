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
    public array $pendingRequests = [];

    public function display($tpl = null)
    {
        Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        $this->course = $this->get('Course');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->rosterForm = $this->get('RosterForm');
        $this->pendingRequests = Factory::getApplication()->bootComponent('com_jlms')->getEnrollmentApprovalService()->pending(Factory::getApplication()->getIdentity(), (int) $this->course->id);
        $this->getDocument()->getWebAssetManager()->useScript('com_jlms.rosterFilters');
        ToolbarHelper::title(Text::_('COM_JLMS_ENROLLMENTS_TITLE'), 'users');
        $courseId = (int) $this->course->id;
        ToolbarHelper::link('index.php?option=com_jlms&view=courses', 'COM_JLMS_COURSES', 'arrow-left');
        ToolbarHelper::link('index.php?option=com_jlms&view=manualenrollment&id=' . $courseId, 'COM_JLMS_MANUAL_TITLE', 'plus');
        ToolbarHelper::link('index.php?option=com_jlms&view=coursemessage&id=' . $courseId, 'COM_JLMS_COMMUNICATION_TITLE', 'envelope');
        ToolbarHelper::link('index.php?option=com_jlms&view=courseoperations&id=' . $courseId . '&operation=admissions_close', 'COM_JLMS_COURSE_OPERATIONS_TITLE', 'sliders-h');
        ToolbarHelper::help('enrollments', true);
        parent::display($tpl);
    }
}
