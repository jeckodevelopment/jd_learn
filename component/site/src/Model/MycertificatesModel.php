<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/** FE-05: use the existing owner-scoped certificate projection. */
final class MycertificatesModel extends BaseDatabaseModel
{
    public function getItems(): array
    {
        $app = Factory::getApplication();
        return $app->bootComponent('com_jlms')->getCertificateService()->listForStudent($app->getIdentity());
    }
}
