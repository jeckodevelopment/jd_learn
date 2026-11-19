<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

/**
 * FE-11 catalogue display settings. Matching courses/category menu items may
 * override component defaults. Never use presentation settings for ACL.
 */
final class CatalogOptions
{
    private const DEFAULTS = [
        'catalog_show_filters' => '1',
        'catalog_show_order' => '1',
        'catalog_show_category' => '1',
        'catalog_show_cover' => '1',
        'catalog_show_summary' => '1',
        'catalog_page_size' => '12',
        'catalog_default_order' => 'editorial',
    ];

    public static function get(string $key): string
    {
        if (!array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException('Unknown catalogue option.');
        }

        $allowed = match ($key) {
            'catalog_page_size' => ['6', '9', '12', '24'],
            'catalog_default_order' => ['editorial', 'recent', 'title', 'duration', 'published'],
            default => ['0', '1'],
        };

        $global = (string) ComponentHelper::getParams('com_jlms')->get($key, self::DEFAULTS[$key]);
        $value = in_array($global, $allowed, true) ? $global : self::DEFAULTS[$key];

        $menu = Factory::getApplication()->getMenu()->getActive();
        if ($menu && ($menu->query['option'] ?? '') === 'com_jlms'
            && (($menu->query['view'] ?? '') === 'courses'
                || (($menu->query['view'] ?? '') === 'category'
                    && (int) ($menu->query['id'] ?? 0) > 0
                    && (int) ($menu->query['id'] ?? 0) === Factory::getApplication()->getInput()->getInt('id', 0)))) {
            $local = (string) $menu->getParams()->get($key, '');
            if (in_array($local, $allowed, true)) {
                $value = $local;
            }
        }

        return $value;
    }

    public static function enabled(string $key): bool
    {
        return self::get($key) === '1';
    }
}
