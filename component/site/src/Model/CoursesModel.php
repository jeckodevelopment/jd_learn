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
        $filter = Factory::getApplication()->getInput()->get('filter', [], 'array');
        $search = $filter['search'] ?? '';
        $this->setState('filter.search', is_string($search) ? trim(mb_substr($search, 0, 100)) : '');
        $order = $filter['order'] ?? 'recent';
        $this->setState('filter.order', is_string($order) && in_array($order, ['recent', 'title'], true) ? $order : 'recent');
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $query = $app->bootComponent('com_jlms')->getCatalogService()->query($app->getIdentity(), $app->getLanguage()->getTag(),
            (int) $this->getState('filter.category_id'));
        $search = (string) $this->getState('filter.search');
        if ($search !== '') {
            $query->where('(c.title LIKE :catalogSearchTitle OR c.summary LIKE :catalogSearchSummary)')
                ->bind(':catalogSearchTitle', '%' . $search . '%')
                ->bind(':catalogSearchSummary', '%' . $search . '%');
        }
        return $query->order($this->getState('filter.order') === 'title' ? 'c.title ASC, c.id ASC' : 'c.id DESC');
    }

    public function getSearch(): string
    {
        return (string) $this->getState('filter.search');
    }

    public function getOrder(): string
    {
        return (string) $this->getState('filter.order');
    }
}
