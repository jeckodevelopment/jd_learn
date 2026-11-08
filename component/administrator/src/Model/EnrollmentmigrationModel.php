<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
final class EnrollmentmigrationModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.enrollmentmigration','enrollmentmigration',['control'=>'jform','load_data'=>false]);
    }
    public function getItem(): array
    {
        try {
            $app=Factory::getApplication(); $input=$app->getInput();
            $course=CurriculumService::id($input->get('id',0,'raw'));
            $enrollment=CurriculumService::id($input->get('enrollment_id',0,'raw'));
            $raw=$input->get('map',[], 'array'); if (!is_array($raw)) { throw new \InvalidArgumentException(); }
            $mapping=[];
            $hasMapping=$input->exists('map');
            foreach ($raw as $from=>$to) {
                if ($to === '' || $to === null) { continue; }
                if ((!is_int($from) && !is_string($from)) || (!is_int($to) && !is_string($to))) { throw new \InvalidArgumentException(); }
                $mapping[$from]=$to;
            }
            $impact=$app->bootComponent('com_jlms')->getEnrollmentMigrationService()->preview($app->getIdentity(),$course,$enrollment,$mapping,!$hasMapping);
            return ['course_id'=>$course,'impact'=>$impact];
        } catch (\Throwable $error) {
            if ($error instanceof \InvalidArgumentException) { throw new \RuntimeException(Text::_('COM_JLMS_INVALID_INPUT'),400); }
            if (in_array($error->getCode(),[400,401,403,404,409,422],true)
                && in_array($error->getMessage(),['COM_JLMS_INVALID_INPUT','COM_JLMS_LOGIN_REQUIRED','COM_JLMS_LEARNING_DENIED','COM_JLMS_RESOURCE_NOT_FOUND',
                    'COM_JLMS_MIGRATION_VERSION_INVALID','COM_JLMS_MIGRATION_TARGET_NOT_CURRENT','COM_JLMS_MIGRATION_SOURCE_INVALID','COM_JLMS_MIGRATION_LIMIT',
                    'COM_JLMS_MIGRATION_MAPPING_INVALID','COM_JLMS_MIGRATION_MAPPING_CONFLICT','COM_JLMS_MIGRATION_TARGET_EXISTS','COM_JLMS_MIGRATION_OPEN_ATTEMPT',
                    'COM_JLMS_MIGRATION_EDITION_UNAVAILABLE','COM_JLMS_MIGRATION_EDITION_FULL','COM_JLMS_COURSE_DELIVERY_SUSPENDED'],true)) {
                throw new \RuntimeException(Text::_($error->getMessage()),$error->getCode());
            }
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'),500);
        }
    }
}
