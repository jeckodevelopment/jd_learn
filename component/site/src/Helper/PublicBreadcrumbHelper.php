<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Factory;

/**
 * Complete the native Joomla breadcrumb for a detail page reached from a
 * listing, without repeating an already active direct menu item.
 */
final class PublicBreadcrumbHelper
{
    public static function addDetail(string $view, int $id, string $title): void
    {
        $app = Factory::getApplication();
        $active = $app->getMenu()->getActive();
        $query = $active?->query ?? [];
        if (
            ($query['option'] ?? '') === 'com_jlms'
            && ($query['view'] ?? '') === $view
            && (int) ($query['id'] ?? 0) === $id
        ) {
            return;
        }

        $pathway = $app->getPathway();
        $items = $pathway->getPathway();
        $last = $items ? end($items) : null;
        if ($last !== null && (string) ($last->name ?? '') === $title) {
            return;
        }

        $pathway->addItem($title);
    }
}
