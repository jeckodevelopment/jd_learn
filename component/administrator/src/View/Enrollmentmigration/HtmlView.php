<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Enrollmentmigration;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
    public array $item; public $form;
    public function display($tpl=null)
    {
        Factory::getApplication()->setHeader('Cache-Control','private, no-store',true);
        $this->item=$this->get('Item'); $this->form=$this->get('Form'); $p=$this->item['impact'];
        $key=bin2hex(random_bytes(16));
        $key=substr($key,0,8).'-'.substr($key,8,4).'-4'.substr($key,13,3).'-a'.substr($key,17,3).'-'.substr($key,20);
        $this->form->bind(['course_id'=>$this->item['course_id'],'enrollment_id'=>$p['source_enrollment_id'],
            'source_revision'=>$p['source_revision'],'impact_hash'=>$p['hash'],'mutation_id'=>$key]);
        ToolbarHelper::title(Text::_('COM_JLMS_MIGRATION_TITLE'),'users'); parent::display($tpl);
    }
}
