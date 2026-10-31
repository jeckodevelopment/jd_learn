<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Quiztime;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView
{
    public array $item;public $form;
    public function display($tpl=null)
    {
        \Joomla\CMS\Factory::getApplication()->setHeader('Cache-Control','private, no-store',true);
        $this->item=$this->get('Item');$this->form=$this->get('Form');$i=$this->item;$key=bin2hex(random_bytes(16));
        $key=substr($key,0,8).'-'.substr($key,8,4).'-4'.substr($key,13,3).'-a'.substr($key,17,3).'-'.substr($key,20);
        $this->form->bind(['course_id'=>$i['course_id'],'lesson_id'=>$i['lesson_id'],'enrollment_id'=>$i['enrollment']?->id??0,
            'revision'=>$i['current']?->revision_no??0,'extra_minutes'=>$i['current']?intdiv((int)$i['current']->extra_seconds,60):0,'mutation_id'=>$key]);
        \Joomla\CMS\Toolbar\ToolbarHelper::title(\Joomla\CMS\Language\Text::_('COM_JLMS_QUIZ_TIME_TITLE'),'clock');parent::display($tpl);
    }
}
