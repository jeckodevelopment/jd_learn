<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
final class CoursemessageModel extends BaseDatabaseModel
{
    public function getItem(): array
    {
        try {
            $app=Factory::getApplication();
            $courseId=CurriculumService::id($app->getInput()->get('id',0,'raw'));
            $data=$app->bootComponent('com_jlms')->getCourseCommunicationService()->editor($app->getIdentity(),$courseId);
            $key='com_jlms.coursemessage.'.(int)$app->getIdentity()->id.'.'.$courseId;
            $preview=$app->getSession()->get($key, null);
            if (is_array($preview)) {
                $data['preview']=$app->bootComponent('com_jlms')->getCourseCommunicationService()->preview($app->getIdentity(),$courseId,(string)$preview['subject'],(string)$preview['body'],$preview['enrollment_ids']);
            }
            $data['preview_key']=$key;
            return $data;
        } catch (\Throwable $error) {
            if ($error instanceof \InvalidArgumentException) { throw new \RuntimeException(Text::_('COM_JLMS_INVALID_INPUT'),400); }
            if (in_array($error->getCode(),[400,401,403,404,409],true) && str_starts_with($error->getMessage(),'COM_JLMS_')) { throw new \RuntimeException(Text::_($error->getMessage()),$error->getCode()); }
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'),500);
        }
    }
}
