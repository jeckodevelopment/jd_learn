<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;

final class CategoryModel extends CoursesModel
{
    protected function populateState($ordering = 'c.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
        $this->setState('filter.category_id', Factory::getApplication()->getInput()->getInt('id', 0));
    }

    public function getCategory(): object
    {
        $app = Factory::getApplication();
        return $app->bootComponent('com_jlms')->getCatalogService()->category($app->getIdentity(), $app->getLanguage()->getTag(), (int) $this->getState('filter.category_id'));
    }

    protected function getListQuery()
    {
        $this->getCategory();
        return parent::getListQuery();
    }
}
