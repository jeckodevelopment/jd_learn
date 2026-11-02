<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Helper\MediaHelper;

/** Public promotional images only; never accepts protected storage or remote URLs. */
final class CourseArtwork
{
    public static function path(string $value): string
    {
        if ($value === '') { return ''; }
        $marker = strpos($value, '#');
        if ($marker !== false && !str_starts_with(substr($value, $marker), '#joomlaImage://')) {
            throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_INVALID');
        }
        $path = MediaHelper::getCleanMediaFieldValue($value);
        if (strlen($value) > 4096 || strlen($path) > 1024 || preg_match('//u', $path) !== 1) {
            throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_INVALID');
        }
        $parts = explode('/', $path);
        if (count($parts) < 2 || count($parts) > 32) { throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_INVALID'); }
        foreach ($parts as $part) {
            if (!preg_match('/^[\p{L}\p{N}_][\p{L}\p{N}_. -]*$/uD', $part) || str_ends_with($part, '.')) {
                throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_INVALID');
            }
        }
        if (!preg_match('/\.(?:png|jpe?g|gif|webp|avif)$/iD', $path)) {
            throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_INVALID');
        }
        return $path;
    }

    /** Validate at save/publication, not while reading history. No remote fetch. */
    public static function validateLocal(string $path): void
    {
        if ($path === '') { return; }
        $path = self::path($path);
        $parts = explode('/', $path);
        array_pop($parts);
        $base = realpath(JPATH_ROOT);
        $file = realpath(JPATH_ROOT . '/' . $path);
        if (!$base || !$file || !is_file($file) || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
            throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_INVALID');
        }
        $allowed = false;
        for ($i = 1; $i <= count($parts); $i++) {
            $directory = implode('/', array_slice($parts, 0, $i));
            if (!MediaHelper::isValidLocalDirectory($directory)) { continue; }
            $root = realpath(JPATH_ROOT . '/' . $directory);
            if ($root && str_starts_with($root, $base . DIRECTORY_SEPARATOR)
                && str_starts_with($file, $root . DIRECTORY_SEPARATOR)) { $allowed = true; break; }
        }
        $bytes = filesize($file);
        if (!$allowed || $bytes === false || $bytes > 10485760) { throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_INVALID'); }
        $image = @getimagesize($file);
        $mime = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'avif' => 'image/avif'][strtolower(pathinfo($path, PATHINFO_EXTENSION))];
        if (!$image || ($image['mime'] ?? '') !== $mime || $image[0] < 1 || $image[1] < 1
            || $image[0] > 20000 || $image[1] > 20000 || $image[0] * $image[1] > 40000000) {
            throw new \InvalidArgumentException('COM_JLMS_COURSE_IMAGE_INVALID');
        }
    }
}
