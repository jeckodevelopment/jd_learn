<?php
declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\FormModel;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
final class CourseoperationsModel extends FormModel
{
 public function getForm($data=[], $loadData=true) { return $this->loadForm('com_jlms.courseoperations','courseoperations',['control'=>'jform','load_data'=>false]); }
 public function getItem(): array
 {
  $app=Factory::getApplication(); $raw=$app->getInput()->get('operation','admissions_close','raw');
  if(!is_string($raw)) throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
  try { return $app->bootComponent('com_jlms')->getCourseOperationsService()->editor($app->getIdentity(),CurriculumService::id($app->getInput()->get('id',0,'raw')),$raw); }
  catch(\InvalidArgumentException) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400); }
 }
}
