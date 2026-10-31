<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Questionbank;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView
{
    public array $bank; public array $item; public $form;
    public function display($tpl = null)
    {
        $this->bank=$this->get('Bank');$this->item=$this->get('Item');$this->form=$this->get('Form');$this->form->bind($this->item);
        \Joomla\CMS\Toolbar\ToolbarHelper::title(\Joomla\CMS\Language\Text::_('COM_JLMS_QUESTION_BANK'),'list');
        parent::display($tpl);
    }
}
