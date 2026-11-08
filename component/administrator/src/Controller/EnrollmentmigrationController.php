<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Controller;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
final class EnrollmentmigrationController extends BaseController
{
    public function execute($task)
    {
        if (strtolower($task)!=='apply') { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND',404); }
        return parent::execute($task);
    }
    public function apply(): void
    {
        $this->app->setHeader('Cache-Control','private, no-store',true);
        if (strtoupper($this->input->getMethod())!=='POST') { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'),405); }
        if (!$this->checkToken('post',false)) { throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'),403); }
        try {
            if ((int)$this->input->server->get('CONTENT_LENGTH',0,'UINT')>32768) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT',413); }
            $raw=$this->input->post->get('jform',[],'array');
            foreach (['course_id','enrollment_id','source_revision','impact_hash','mutation_id','reason','confirmation'] as $key) {
                if (!is_string($raw[$key]??null)) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400); }
            }
            if ($raw['confirmation']!=='1') { throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400); }
            $mapping=$raw['mapping']??[]; if (!is_array($mapping)||count($mapping)>100) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400); }
            foreach ($mapping as $from=>$to) {
                if (!is_string($to)||!preg_match('/\A[1-9][0-9]{0,18}\z/D',(string)$from)||!preg_match('/\A[1-9][0-9]{0,18}\z/D',$to)) {
                    throw new \RuntimeException('COM_JLMS_INVALID_INPUT',400);
                }
            }
            $form=$this->getModel('Enrollmentmigration','Administrator',['ignore_request'=>true])->getForm();
            $fields=array_intersect_key($raw,array_flip(['course_id','enrollment_id','source_revision','impact_hash','mutation_id','reason','confirmation']));
            if (!$form->validate($form->filter($fields))) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT',422); }
            $course=CurriculumService::id($raw['course_id']); $enrollment=CurriculumService::id($raw['enrollment_id']);
            $target=$this->app->bootComponent('com_jlms')->getEnrollmentMigrationService()->apply($this->app->getIdentity(),$course,$enrollment,
                CurriculumService::id($raw['source_revision']),$mapping,$raw['reason'],$raw['mutation_id'],$raw['impact_hash'],true);
            $this->setMessage(Text::_('COM_JLMS_MIGRATION_SAVED'));
            $this->setRedirect(Route::link('administrator','index.php?option=com_jlms&view=enrollments&id='.$course,false));
        } catch (\Throwable $error) {
            $key=$error->getMessage(); $code=(int)$error->getCode();
            if ($error instanceof \InvalidArgumentException) { $key='COM_JLMS_INVALID_INPUT'; $code=400; }
            if (in_array($code,[400,401,403,404,409,413,422,503],true) && in_array($key,[
                'COM_JLMS_INVALID_INPUT','COM_JLMS_LOGIN_REQUIRED','COM_JLMS_LEARNING_DENIED','COM_JLMS_RESOURCE_NOT_FOUND',
                'COM_JLMS_MIGRATION_VERSION_INVALID','COM_JLMS_MIGRATION_TARGET_NOT_CURRENT','COM_JLMS_MIGRATION_SOURCE_INVALID',
                'COM_JLMS_MIGRATION_LIMIT','COM_JLMS_MIGRATION_MAPPING_INVALID','COM_JLMS_MIGRATION_MAPPING_CONFLICT',
                'COM_JLMS_MIGRATION_TARGET_EXISTS','COM_JLMS_MIGRATION_OPEN_ATTEMPT','COM_JLMS_MIGRATION_CONFLICT',
                'COM_JLMS_MIGRATION_EDITION_MISSING','COM_JLMS_MIGRATION_EDITION_UNAVAILABLE','COM_JLMS_MIGRATION_EDITION_FULL',
                'COM_JLMS_COURSE_DELIVERY_SUSPENDED','COM_JLMS_MIGRATION_REASON','COM_JLMS_MIGRATION_AUDIT_FAILED'],true)) {
                throw new \RuntimeException(Text::_($key),$code);
            }
            \Joomla\CMS\Log\Log::add('Enrollment migration failed; correlation='.bin2hex(random_bytes(8)),\Joomla\CMS\Log\Log::ERROR,'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'),500);
        }
    }
}
