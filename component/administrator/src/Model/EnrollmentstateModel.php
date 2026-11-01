<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
final class EnrollmentstateModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.enrollmentstate', 'enrollmentstate', ['control' => 'jform', 'load_data' => false]);
    }
    public function getItem(): array
    {
        try {
            $app = Factory::getApplication(); $input = $app->getInput();
            $target = $input->get('target_status', '', 'raw');
            if (!is_string($target)) { throw new \InvalidArgumentException(); }
            return $app->bootComponent('com_jlms')->getEnrollmentStateService()->editor($app->getIdentity(),
                CurriculumService::id($input->get('id', 0, 'raw')), CurriculumService::id($input->get('enrollment_id', 0, 'raw')), $target);
        } catch (\Throwable $error) {
            if ($error instanceof \InvalidArgumentException) { throw new \RuntimeException(Text::_('COM_JLMS_INVALID_INPUT'), 400); }
            if (in_array($error->getCode(), [400, 401, 403, 404], true) && in_array($error->getMessage(), ['COM_JLMS_INVALID_INPUT', 'COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_RESOURCE_NOT_FOUND'], true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode());
            }
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
