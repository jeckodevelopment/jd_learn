<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Question;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public $form;

    public function display($tpl = null)
    {
        $actor=Factory::getApplication()->getIdentity();
        if (!$actor->id||$actor->block||!$actor->authorise('core.manage','com_jlms')||!$actor->authorise('jlms.quiz.manage','com_jlms')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
        $this->item=$this->get('Item');$this->form=$this->get('Form');$this->form->bind($this->item);
        ToolbarHelper::title(Text::_($this->item['id']?'COM_JLMS_QUESTION_EDIT':'COM_JLMS_QUESTION_NEW'),'help');
        ToolbarHelper::save('questionbank.save');
        ToolbarHelper::link('index.php?option=com_jlms&view=questionbank','JCANCEL','cancel');
        parent::display($tpl);
    }
}
