<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Courseannouncement;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;

final class HtmlView extends BaseHtmlView
{
    public array $preview = [];

    public function display($tpl = null)
    {
        $id = Factory::getApplication()->getInput()->getInt('id', 0);
        $service = new \Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementService(Factory::getContainer()->get(DatabaseInterface::class));
        $this->preview = $service->preview(Factory::getApplication()->getIdentity(), $id);
        $this->getDocument()->setTitle(Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_TITLE'));
        ToolbarHelper::title(Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_TITLE'), 'envelope');
        // #406: show the return action as the first native toolbar button.
        $this->getDocument()->getToolbar()
            ->linkButton('back-to-course', 'COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_BACK')
            ->icon('icon-arrow-left')
            ->url(Route::link('administrator', 'index.php?option=com_jlms&view=course&layout=edit&id=' . (int) $this->preview['course_id']));
        ToolbarHelper::help('courseannouncement', true);
        parent::display($tpl);
    }
}
