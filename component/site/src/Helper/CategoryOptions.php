<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

/** FE-11: category sections inherit global defaults, with exact menu identity only. */
final class CategoryOptions
{
    private const KEYS = ['subcategories' => 'category_show_subcategories', 'courses' => 'category_show_courses'];

    public static function sections(int $categoryId): array
    {
        $params = ComponentHelper::getParams('com_jlms');
        $menu = Factory::getApplication()->getMenu()->getActive();
        $matching = $menu && ($menu->query['option'] ?? '') === 'com_jlms'
            && ($menu->query['view'] ?? '') === 'category'
            && (int) ($menu->query['id'] ?? 0) === $categoryId && $categoryId > 0;
        $options = [];
        foreach (self::KEYS as $section => $key) {
            $global = (string) $params->get($key, '1');
            $value = in_array($global, ['0', '1'], true) ? $global : '1';
            if ($matching) {
                $local = (string) $menu->getParams()->get($key, '');
                if (in_array($local, ['0', '1'], true)) {
                    $value = $local;
                }
            }
            $options[$section] = $value === '1';
        }
        return $options;
    }
}
