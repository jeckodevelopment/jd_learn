<?php
declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Coursemessage;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public array $item;
    public function display($tpl=null)
    {
        Factory::getApplication()->setHeader('Cache-Control','private, no-store',true);
        $this->item=$this->get('Item');
        ToolbarHelper::title(Text::_('COM_JLMS_COMMUNICATION_TITLE'),'mail');
        parent::display($tpl);
    }
}
