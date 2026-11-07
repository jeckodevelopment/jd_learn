<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;

final class CourseModel extends ItemModel
{
    public function getItem($pk = null)
    {
        $app = Factory::getApplication();
        // Native com_fields reads the application identity, so refresh its access
        // together with the catalog check rather than using session-cached grants.
        $actor = new \Joomla\CMS\User\User((int) $app->getIdentity()->id);
        $actor->clearAccessRights();
        $app->loadIdentity($actor);
        $id = (int) ($pk ?? $app->getInput()->getInt('id', 0));
        $item = $app->bootComponent('com_jlms')->getCatalogService()->outline($actor, $app->getLanguage()->getTag(), $id);
        $item['fields'] = \Jlms\Component\Jlms\Site\Helper\CourseFields::prepare($item['course'], $app->getLanguage()->getTag());
        return $item;
    }
}
