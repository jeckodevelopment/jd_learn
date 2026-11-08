<?php
declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Courseoperations;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView
{
 public array $item; public $form;
 public function display($tpl=null) {
  Factory::getApplication()->setHeader('Cache-Control','private, no-store',true); $this->item=$this->get('Item'); $this->form=$this->get('Form');
  $p=$this->item['impact']; $id=bin2hex(random_bytes(16)); $id=substr($id,0,8).'-'.substr($id,8,4).'-4'.substr($id,13,3).'-a'.substr($id,17,3).'-'.substr($id,20);
  $this->form->bind(['course_id'=>(int)$this->item['course']->id,'operation'=>$p['operation'],'course_revision'=>$p['revision'],'impact_hash'=>$p['hash'],'mutation_id'=>$id]);
  ToolbarHelper::title(Text::_('COM_JLMS_COURSE_OPERATIONS_TITLE'),'lock'); parent::display($tpl);
 }
}
