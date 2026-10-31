<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\CMS\Application\CMSApplicationInterface;

final class MaterialDownload
{
    /** Authorisation and opening the immutable file must precede this method. */
    public static function send(CMSApplicationInterface $app, array $file): void
    {
        $handle = $file['handle'];
        try {
            $method = strtoupper($app->getInput()->getMethod());
            if (!in_array($method, ['GET', 'HEAD'], true)) { throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405); }
            $size = $file['size']; $start = 0; $end = $size - 1; $status = 200;
            $range = $app->getInput()->server->getString('HTTP_RANGE', '');
            $ifRange = $app->getInput()->server->getString('HTTP_IF_RANGE', '');
            if ($method === 'GET' && $range !== '' && ($ifRange === '' || $ifRange === $file['etag'])) {
                if (!preg_match('/\Abytes=([0-9]{0,16})-([0-9]{0,16})\z/D', $range, $parts) || ($parts[1] === '' && $parts[2] === '')) {
                    $app->setHeader('Content-Range', 'bytes */' . $size, true);
                    throw new \RuntimeException('COM_JLMS_RANGE_INVALID', 416);
                }
                if ($parts[1] === '') {
                    $suffix = (int) $parts[2]; $start = max(0, $size - $suffix);
                    if ($suffix < 1) { $start = $size; }
                } else {
                    $start = (int) $parts[1]; $end = $parts[2] === '' ? $size - 1 : min($size - 1, (int) $parts[2]);
                }
                if ($start >= $size || $end < $start) {
                    $app->setHeader('Content-Range', 'bytes */' . $size, true);
                    throw new \RuntimeException('COM_JLMS_RANGE_INVALID', 416);
                }
                $status = 206;
            }
            MaterialFilePolicy::name($file['name']);
            $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $file['name'])) ?: 'download';
            $app->setHeader('status', $status === 206 ? '206 Partial Content' : '200 OK', true);
            $app->setHeader('Content-Type', $file['mime'], true);
            $app->setHeader('Content-Disposition', 'attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($file['name']), true);
            $app->setHeader('Content-Length', (string) ($end - $start + 1), true);
            $app->setHeader('Accept-Ranges', 'bytes', true);
            $app->setHeader('ETag', $file['etag'], true);
            $app->setHeader('X-Content-Type-Options', 'nosniff', true);
            $app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
            $app->setHeader('Cache-Control', 'private, no-store', true);
            if ($status === 206) { $app->setHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $size, true); }
            $app->getSession()->close();
            $app->sendHeaders();
            if ($method !== 'HEAD') {
                fseek($handle, $start); $remaining = $end - $start + 1;
                while ($remaining > 0 && !connection_aborted()) {
                    $chunk = fread($handle, min(65536, $remaining));
                    if ($chunk === false || $chunk === '') { break; }
                    echo $chunk; $remaining -= strlen($chunk);
                }
            }
        } finally {
            fclose($handle);
        }
        $app->close();
    }
}
