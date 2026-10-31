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
        $app->getPathway()->addItem(Text::_('COM_JLMS_CATEGORIES'), 'index.php?option=com_jlms&view=categories');
        foreach ($path as $category) {
            $app->getPathway()->addItem($category->title, (int) $category->id === $id ? '' : 'index.php?option=com_jlms&view=category&id=' . (int) $category->id);
        }
    }
}
