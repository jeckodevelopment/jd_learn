<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Quizextend;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView
{
    public array $item;public $form;
    public function display($tpl=null)
    {
        \Joomla\CMS\Factory::getApplication()->setHeader('Cache-Control','private, no-store',true);
        $this->item=$this->get('Item');$this->form=$this->get('Form');$i=$this->item;$key=bin2hex(random_bytes(16));
        $key=substr($key,0,8).'-'.substr($key,8,4).'-4'.substr($key,13,3).'-a'.substr($key,17,3).'-'.substr($key,20);
        $this->form->bind(['course_id'=>$i['course_id'],'lesson_id'=>$i['lesson_id'],'enrollment_id'=>(int)$i['enrollment']->id,'attempt_id'=>(int)$i['attempt']->id,
            'enrollment_revision'=>$i['impact']['enrollment_revision'],'attempt_revision'=>$i['impact']['attempt_revision'],'impact_hash'=>$i['impact']['hash'],
            'minutes'=>$i['minutes'],'mutation_id'=>$key]);
        \Joomla\CMS\Toolbar\ToolbarHelper::title(\Joomla\CMS\Language\Text::_('COM_JLMS_QUIZ_EXTEND_TITLE'),'clock');parent::display($tpl);
    }
}
