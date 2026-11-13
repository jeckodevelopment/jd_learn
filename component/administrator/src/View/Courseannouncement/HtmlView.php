<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Courseannouncement;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $preview = [];

    public function display($tpl = null)
    {
        $id = Factory::getApplication()->getInput()->getInt('id', 0);
        $service = Factory::getContainer()->get(\Jlms\Component\Jlms\Administrator\Application\CourseAnnouncementService::class);
        $this->preview = $service->preview(Factory::getApplication()->getIdentity(), $id);
        $this->getDocument()->setTitle(Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_TITLE'));
        parent::display($tpl);
    }
}
