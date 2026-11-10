<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Helper;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** Adds share metadata only for records that the current visitor may discover. */
final class PublicMetadataHelper
{
    public static function apply(object $document, string $title, string $description, string $url, string $image = ''): void
    {
        $title = trim($title);
        $description = mb_substr(trim(strip_tags($description)), 0, 300, 'UTF-8');
        $canonical = Uri::root() . ltrim(Route::_($url), '/');
        $document->addHeadLink($canonical, 'canonical');

        if ($title !== '') {
            $document->setMetaData('og:title', $title, 'property');
            $document->setMetaData('twitter:title', $title);
        }
        if ($description !== '') {
            $document->setDescription($description);
            $document->setMetaData('og:description', $description, 'property');
            $document->setMetaData('twitter:description', $description);
        }

        $document->setMetaData('og:type', 'website', 'property');
        $document->setMetaData('og:url', $canonical, 'property');
        $document->setMetaData('twitter:card', 'summary_large_image');

        // Only local Joomla media paths are eligible for social previews.
        if ($image !== '' && preg_match('#^(?:images|media)/[\p{L}\p{N}_./ -]+\.(?:png|jpe?g|gif|webp|avif)$#iuD', $image)
            && !str_contains($image, '..')) {
            $imagePath = implode('/', array_map('rawurlencode', explode('/', trim($image, '/'))));
            $imageUrl = Uri::root() . $imagePath;
            $document->setMetaData('og:image', $imageUrl, 'property');
            $document->setMetaData('twitter:image', $imageUrl);
        }
    }
}
