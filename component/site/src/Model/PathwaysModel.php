<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

final class PathwaysModel extends ListModel
{
    protected function populateState($ordering = 'cat.lft, p.title', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
        $this->setState('list.limit', max(1, min(50, (int) $this->state->get('list.limit', 20))));
    }

    protected function getStoreId($id = '')
    {
        $app = Factory::getApplication();
        return parent::getStoreId($id . ':' . (int) $app->getIdentity()->id . ':' . $app->getLanguage()->getTag());
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        return $app->bootComponent('com_jlms')->getPathwayService()
            ->query($app->getIdentity(), $app->getLanguage()->getTag());
    }
}
