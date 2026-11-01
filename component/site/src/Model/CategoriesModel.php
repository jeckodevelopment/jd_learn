<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Database\ParameterType;

final class CategoriesModel extends ListModel
{
    protected function populateState($ordering = 'cat.lft', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
        $this->setState('list.limit', max(1, min(100, (int) $this->state->get('list.limit', 20))));
        $this->setState('filter.parent_id', max(0, Factory::getApplication()->getInput()->getInt('parent_id', 0)));
    }

    public function getParent(): ?object
    {
        $id = (int) $this->getState('filter.parent_id', 0);
        if (!$id) { return null; }
        $app = Factory::getApplication();
        return $app->bootComponent('com_jlms')->getCatalogService()->category($app->getIdentity(), $app->getLanguage()->getTag(), $id);
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $parent = $this->getParent();
        $id = $parent ? (int) $parent->id : 1;
        return $app->bootComponent('com_jlms')->getCatalogService()->categoryQuery($app->getIdentity(), $app->getLanguage()->getTag())
            ->where('cat.parent_id = :parentId')->bind(':parentId', $id, ParameterType::INTEGER)->order('cat.lft, cat.id');
    }

    public function getPagination()
    {
        return new Pagination($this->getTotal(), $this->getStart(), (int) $this->getState('list.limit', 20), (string) $this->getState('pagination.prefix', ''));
    }
}
