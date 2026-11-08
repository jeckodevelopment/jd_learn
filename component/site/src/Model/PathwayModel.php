<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;

final class PathwayModel extends ItemModel
{
    public function getItem($pk = null): array
    {
        $app = Factory::getApplication();
        $id = (int) ($pk ?? $app->getInput()->getInt('id', 0));
        return $app->bootComponent('com_jlms')->getPathwayService()
            ->detail($app->getIdentity(), $app->getLanguage()->getTag(), $id);
    }
}
