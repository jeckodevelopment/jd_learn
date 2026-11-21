<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Pagination\Pagination;

/**
 * SCR-01: preserve only validated public catalogue filters across Joomla page links.
 * Joomla does not copy arbitrary request query parameters into Pagination links.
 */
final class CatalogPagination
{
    private const ALLOWED = [
        'search', 'order', 'category_id', 'tag_id', 'difficulty', 'duration',
        'available', 'layout', 'teacher_id', 'pathway_id',
    ];

    /**
     * @param array<string,scalar|null> $filters Already normalized by CoursesModel
     */
    public static function apply(Pagination $pagination, array $filters, bool $showFilters, int $categoryId = 0): void
    {
        if ($categoryId > 0) {
            $pagination->setAdditionalUrlParam('catid', $categoryId);
        }
        if (!$showFilters) {
            return;
        }
        $safe = [];
        foreach (self::ALLOWED as $key) {
            $value = $filters[$key] ?? null;
            if (!is_string($value) && !is_int($value)) {
                continue;
            }
            $value = (string) $value;
            if ($value !== '' && $value !== '0') {
                $safe[$key] = $value;
            }
        }
        if ($safe) {
            $pagination->setAdditionalUrlParam('filter', $safe);
        }
    }
}
