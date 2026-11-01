<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

final class CategoryNavigationHelper
{
    public static function addPath(int $id): void
    {
        $app = Factory::getApplication();
        $path = $app->bootComponent('com_jlms')->getCatalogService()->categoryPath($app->getIdentity(), $app->getLanguage()->getTag(), $id);
        $active = $app->getMenu()->getActive();
        $query = $active?->query ?? [];
        $categoryMenu = ($query['option'] ?? '') === 'com_jlms' && in_array($query['view'] ?? '', ['category', 'categories'], true);
        $menuCategory = $categoryMenu ? (int) ($query[($query['view'] === 'category') ? 'id' : 'parent_id'] ?? 0) : 0;
        $matched = $menuCategory > 0 && in_array($menuCategory, array_map(static fn ($item): int => (int) $item->id, $path), true);
        if (!$categoryMenu || ($menuCategory > 0 && !$matched)) {
            $app->getPathway()->addItem(Text::_('COM_JLMS_CATEGORIES'), 'index.php?option=com_jlms&view=categories');
        }
        foreach ($path as $category) {
            if ($matched) {
                if ((int) $category->id === $menuCategory) { $matched = false; }
                continue;
            }
            $app->getPathway()->addItem($category->title, (int) $category->id === $id ? '' : 'index.php?option=com_jlms&view=category&id=' . (int) $category->id);
        }
    }
}
