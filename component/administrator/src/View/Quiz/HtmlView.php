<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Quiz;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView
{
    public array $item; public $form;
    public function display($tpl = null)
    {
        $this->item=$this->get('Item');$this->form=$this->get('Form');
        $field=new \SimpleXMLElement('<field name="module_id" type="list" label="COM_JLMS_MODULE" required="true" />');
        foreach($this->item['modules']as$module){$field->addChild('option',htmlspecialchars($module->title,ENT_XML1))->addAttribute('value',(string)$module->id);}
        $this->form->setField($field);$this->form->bind($this->item['data']);
        \Joomla\CMS\Toolbar\ToolbarHelper::title(\Joomla\CMS\Language\Text::_('COM_JLMS_QUIZ_EDITOR'),'list');parent::display($tpl);
    }
}
