<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Site\View\Courses;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;

final class HtmlView extends BaseHtmlView
{
    public function display($tpl = null)
    {
        $this->getDocument()->setTitle(Text::_('COM_JLMS_COURSES'));
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
