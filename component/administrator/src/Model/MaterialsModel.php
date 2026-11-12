<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\FormModel;

final class MaterialsModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.material_upload', 'material_upload', ['control' => 'jform', 'load_data' => false]);
    }

    public function getItem()
    {
        $app = Factory::getApplication();
        try {
            return $app->bootComponent('com_jlms')->getMaterialService()->administration($app->getIdentity(),
                $app->getInput()->getInt('id', 0), $app->getInput()->getInt('version_id', 0), $app->getInput()->getInt('page', 0),
                $app->getInput()->getInt('material_scope', 0));
        } catch (\RuntimeException $error) {
            $known = ['COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED'];
            if (in_array($error->getMessage(), $known, true)) {
                throw new \RuntimeException(\Joomla\CMS\Language\Text::_($error->getMessage()), $error->getCode());
            }
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
