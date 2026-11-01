<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;

class CoursesModel extends ListModel
{
    protected function populateState($ordering = 'c.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
        $this->setState('list.limit', max(1, min(100, (int) $this->state->get('list.limit', 20))));
        $this->setState('filter.category_id', max(0, Factory::getApplication()->getInput()->getInt('catid', 0)));
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        return $app->bootComponent('com_jlms')->getCatalogService()->query($app->getIdentity(), $app->getLanguage()->getTag(),
            (int) $this->getState('filter.category_id'))->order('c.id DESC');
    }
}
