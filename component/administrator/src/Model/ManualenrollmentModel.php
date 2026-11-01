<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Component\ComponentHelper;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
final class ManualenrollmentModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.manualenrollment', 'manualenrollment', ['control' => 'jform', 'load_data' => false]);
    }
    public function getSelectionForm(): Form
    {
        return Form::getInstance('com_jlms.manualselection', JPATH_ADMINISTRATOR.'/components/com_jlms/forms/manualselection.xml', ['control' => 'filter']);
    }
    public function getItem(): array
    {
        try {
            $app = Factory::getApplication(); $input = $app->getInput();
            $id = CurriculumService::id($input->get('id', 0, 'raw'));
            $course = $app->bootComponent('com_jlms')->getEnrollmentRosterService()->course($app->getIdentity(), $id);
            $filter = $input->get('filter', [], 'array');
            $user = CurriculumService::id($filter['user_id'] ?? 0, true);
            $language = $filter['language'] ?? (string) ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
            if (!is_string($language)) { throw new \InvalidArgumentException(); }
            $preview = $user ? $app->bootComponent('com_jlms')->getManualEnrollmentService()->editor($app->getIdentity(), $id, $user, $language) : null;
            return ['course' => $course, 'selection' => ['user_id' => $user, 'language' => $language], 'preview' => $preview];
        } catch (\Throwable $error) {
            if ($error instanceof \InvalidArgumentException) { throw new \RuntimeException(Text::_('COM_JLMS_INVALID_INPUT'), 400); }
            if (in_array($error->getCode(), [400,401,403,404,409], true) && in_array($error->getMessage(), ['COM_JLMS_INVALID_INPUT','COM_JLMS_LOGIN_REQUIRED','COM_JLMS_LEARNING_DENIED','COM_JLMS_LEARNING_WINDOW','COM_JLMS_RESOURCE_NOT_FOUND','COM_JLMS_MANUAL_MODE_PENDING','JERROR_LAYOUT_PAGE_NOT_FOUND'], true)) { throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode()); }
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
