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
        return $app->bootComponent('com_jlms')->getMaterialService()->administration($app->getIdentity(),
            $app->getInput()->getInt('id', 0), $app->getInput()->getInt('version_id', 0), $app->getInput()->getInt('page', 0));
    }
}
