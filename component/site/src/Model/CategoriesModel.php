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
        // An absent parent selects the root; a malformed explicit parent must not
        // silently fall back to the root directory or a different category.
        $rawParent = Factory::getApplication()->getInput()->get('parent_id', null, 'RAW');
        if ($rawParent === null) {
            $parentId = 0;
        } elseif (!is_string($rawParent) && !is_int($rawParent)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        } elseif (preg_match('/\\A[0-9]+\\z/D', (string) $rawParent) !== 1) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        } else {
            $digits = ltrim((string) $rawParent, '0');
            $parentId = $digits === '' ? 0 : filter_var($digits, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if ($parentId === false) {
                throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
            }
        }
        $this->setState('filter.parent_id', $parentId);
    }

    public function getParent(): ?object
    {
        $id = (int) $this->getState('filter.parent_id', 0);
        if (!$id) {
            return null;
        }
        $app = Factory::getApplication();
        return $app->bootComponent('com_jlms')->getCatalogService()->category($app->getIdentity(), $app
            ->getLanguage()->getTag(), $id);
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        $parent = $this->getParent();
        $id = $parent ? (int) $parent->id : 1;
        return $app->bootComponent('com_jlms')->getCatalogService()->categoryQuery($app->getIdentity(), $app
            ->getLanguage()->getTag())
            ->where('cat.parent_id = :parentId')->bind(':parentId', $id, ParameterType::INTEGER)
                ->order('cat.lft, cat.id');
    }

    public function getPagination()
    {
        return new Pagination($this->getTotal(), $this->getStart(), (int) $this
            ->getState('list.limit', 20), (string) $this->getState('pagination.prefix', ''));
    }
}
