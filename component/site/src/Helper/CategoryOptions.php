<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Factory;

/**
 * FE-08 per-menu presentation options for the native category detail.
 * Defaults preserve the pre-existing page, and options never alter ACL.
 */
final class CategoryOptions
{
    public static function sections(int $categoryId): array
    {
        $menu = Factory::getApplication()->getMenu()->getActive();
        $options = ['subcategories' => true, 'courses' => true];
        if (!$menu || ($menu->query['option'] ?? '') !== 'com_jlms'
            || ($menu->query['view'] ?? '') !== 'category'
            || (int) ($menu->query['id'] ?? 0) !== $categoryId) {
            return $options;
        }
        $params = $menu->getParams();
        foreach (['subcategories' => 'category_show_subcategories', 'courses' => 'category_show_courses'] as $section => $key) {
            $value = (string) $params->get($key, '1');
            $options[$section] = $value !== '0';
        }
        return $options;
    }
}
