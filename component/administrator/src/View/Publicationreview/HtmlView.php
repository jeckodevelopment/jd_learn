<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Publicationreview;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        ToolbarHelper::title(Text::_('COM_JLMS_PUBLICATION_REVIEW_HEADING'), 'graduation-cap');
        parent::display($tpl);
    }
}
