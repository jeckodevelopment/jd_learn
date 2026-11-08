<?php
declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Controller;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
final class CourseoperationsController extends BaseController
{
 public function execute($task) { if(strtolower($task)!=='apply') throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND',404); return parent::execute($task); }
 public function apply(): void {
  $this->app->setHeader('Cache-Control','private, no-store',true);
  if(strtoupper($this->input->getMethod())!=='POST') throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'),405);
  if(!$this->checkToken('post',false)) throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'),403);
  $raw=$this->input->post->get('jform',[],'array');
  foreach(['course_id','operation','course_revision','reason','mutation_id','impact_hash','confirmation'] as $key) if(!is_string($raw[$key]??null)) throw new \RuntimeException(Text::_('COM_JLMS_INVALID_INPUT'),400);
  if($raw['confirmation']!=='1') throw new \RuntimeException(Text::_('COM_JLMS_INVALID_INPUT'),400);
  try {
   $form=$this->getModel('Courseoperations','Administrator',['ignore_request'=>true])->getForm(); $data=$form->filter($raw);
   if(!$form->validate($data)) throw new \RuntimeException('COM_JLMS_INVALID_INPUT',422);
   $course=CurriculumService::id($raw['course_id']); $revision=CurriculumService::id($raw['course_revision']);
   $this->app->bootComponent('com_jlms')->getCourseOperationsService()->apply($this->app->getIdentity(),$course,$raw['operation'],$revision,$raw['reason'],$raw['mutation_id'],$raw['impact_hash']);
   $this->setMessage(Text::_('COM_JLMS_COURSE_OPERATIONS_SAVED'));
   $this->setRedirect(Route::link('administrator','index.php?option=com_jlms&view=courseoperations&id='.$course.'&operation='.$raw['operation'],false));
  } catch(\Throwable $e) {
   $allowed=['COM_JLMS_INVALID_INPUT','COM_JLMS_LOGIN_REQUIRED','COM_JLMS_LEARNING_DENIED','COM_JLMS_RESOURCE_NOT_FOUND','COM_JLMS_COURSE_OPERATION_REASON','COM_JLMS_COURSE_OPERATION_CONFLICT','COM_JLMS_COURSE_OPERATIONS_AUDIT_FAILED'];
   if(in_array($e->getMessage(),$allowed,true) && in_array((int)$e->getCode(),[400,401,403,404,409,422,503],true)) throw new \RuntimeException(Text::_($e->getMessage()),(int)$e->getCode());
   \Joomla\CMS\Log\Log::add('Course operation failed; correlation='.bin2hex(random_bytes(8)),\Joomla\CMS\Log\Log::ERROR,'com_jlms');
   throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'),500);
  }
 }
}
