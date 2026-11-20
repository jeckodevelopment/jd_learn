<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

/** Turn visitor text into a literal SQL LIKE fragment (MySQL/MariaDB). */
final class CatalogSearch
{
    public static function pattern(string $text): string
    {
        // Use an explicit escape character independent of NO_BACKSLASH_ESCAPES.
        // Bound parameters prevent injection, but alone do not escape % or _.
        return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text) . '%';
    }
}
