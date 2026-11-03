<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Coursepreview;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $preview;

    public function display($tpl = null)
    {
        Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        $this->preview = $this->get('Preview');
        ToolbarHelper::title(Text::_('COM_JLMS_COURSE_PREVIEW'), 'eye');

        return parent::display($tpl);
    }
}
