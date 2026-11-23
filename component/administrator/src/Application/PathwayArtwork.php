<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

/** Validated public cover metadata from persisted pathway configuration. */
final class PathwayArtwork
{
    /** @return array{image:string,alt:string} */
    public static function fromParams(?string $json): array
    {
        try {
            $data = json_decode($json ?: '{}', true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                return ['image' => '', 'alt' => ''];
            }
            $path = CourseArtwork::path((string) ($data['cover_image'] ?? ''));
            $alt = (string) ($data['cover_alt'] ?? '');
            $decorative = ($data['cover_decorative'] ?? false) === true;
            if (
                mb_strlen($alt) > 500 || preg_match('/[\\x00-\\x1f\\x7f]/', $alt)
                || ($path !== '' && !$decorative && $alt === '')
            ) {
                return ['image' => '', 'alt' => ''];
            }
            return ['image' => $path, 'alt' => $decorative ? '' : $alt];
        } catch (\Throwable) {
            // Invalid older/editorial config must not disrupt the public catalog.
            return ['image' => '', 'alt' => ''];
        }
    }
}
