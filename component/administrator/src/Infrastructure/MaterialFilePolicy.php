<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

/** Bounded inspection only. Archives are never extracted and documents are never executed. */
final class MaterialFilePolicy
{
    public const MAX_BYTES = 20971520;

    public static function name(string $name): string
    {
        if ($name === '' || strlen($name) > 180 || preg_match('//u', $name) !== 1
            || preg_match('/[\\x00-\\x1f\\x7f\\/\\\\]/', $name) || $name[0] === '.' || trim($name) !== $name
            || preg_match('/(?:^|\.)(?:php\d*|phtml|phar|exe|com|bat|cmd|sh|ps1|js|html?|svg)(?:\.|$)/i', $name)) {
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        return $name;
    }

    public static function inspect(string $path, string $name, int $limit = self::MAX_BYTES): array
    {
        self::name($name);
        $size = filesize($path);
        if ($size === false || $size < 1 || $size > min(self::MAX_BYTES, $limit)) {
            throw new \RuntimeException('COM_JLMS_FILE_SIZE', 413);
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $types = ['pdf' => ['application/pdf'], 'txt' => ['text/plain'], 'csv' => ['text/plain', 'text/csv', 'application/csv'],
            'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'],
            'zip' => ['application/zip', 'application/octet-stream'],
            'docx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xlsx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'pptx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'odt' => ['application/zip', 'application/vnd.oasis.opendocument.text'],
            'ods' => ['application/zip', 'application/vnd.oasis.opendocument.spreadsheet'],
            'odp' => ['application/zip', 'application/vnd.oasis.opendocument.presentation']];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!isset($types[$ext]) || !in_array($mime, $types[$ext], true)) {
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        $head = file_get_contents($path, false, null, 0, min(1024, $size));
        if ($ext === 'pdf') {
            $tail = file_get_contents($path, false, null, max(0, $size - 1024));
            if (!str_starts_with($head, '%PDF-') || !str_contains($tail, '%%EOF')) {
                throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
            }
        } elseif (in_array($ext, ['txt', 'csv'], true)) {
            $text = file_get_contents($path);
            if (str_contains($text, "\0") || preg_match('//u', $text) !== 1) {
                throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
            }
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $image = @getimagesize($path);
            if (!$image || ($image['mime'] ?? '') !== $mime || $image[0] > 20000 || $image[1] > 20000
                || $image[0] * $image[1] > 40000000) {
                throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
            }
        } else {
            self::archive($path, $ext);
            if ($ext !== 'zip') {
                $mime = $types[$ext][1];
            } else {
                $mime = 'application/zip';
            }
        }
        return ['mime_type' => $mime, 'byte_size' => $size, 'sha256' => hash_file('sha256', $path), 'original_name' => $name];
    }

    private static function archive(string $path, string $ext): void
    {
        $size = filesize($path);
        $tail = file_get_contents($path, false, null, max(0, $size - 65557));
        $at = strrpos($tail, "PK\x05\x06");
        if ($at === false || strlen($tail) - $at < 22) {
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        $end = unpack('vdisk/vdirectory_disk/ventries_disk/ventries/Vdirectory_size/Voffset/vcomment', substr($tail, $at + 4, 18));
        if ($end['disk'] || $end['directory_disk'] || $end['entries'] !== $end['entries_disk']
            || $end['entries'] > 2000 || $end['directory_size'] > 2097152
            || $end['offset'] + $end['directory_size'] > $size || strlen($tail) - $at !== 22 + $end['comment']) {
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CHECKCONS) !== true) {
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        try {
            $expanded = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $name = $entry['name'];
                if (strlen($name) > 255 || preg_match('/[\\x00-\\x1f\\\\]/', $name) || str_starts_with($name, '/')
                    || preg_match('~(?:^|/)\.\.(?:/|$)|^[A-Za-z]:~', $name) || ($entry['encryption_method'] ?? 0) !== 0) {
                    throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
                }
                $expanded += $entry['size'];
                if ($expanded > 104857600 || ($entry['size'] > 1048576 && $entry['size'] > max(1, $entry['comp_size']) * 200)) {
                    throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
                }
                if ($ext !== 'zip' && preg_match('~vbaProject|(?:^|/)(?:activeX|embeddings)/~i', $name)) {
                    throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
                }
            }
            if (in_array($ext, ['docx', 'xlsx', 'pptx'], true)) {
                $main = ['docx' => 'word/document.xml', 'xlsx' => 'xl/workbook.xml', 'pptx' => 'ppt/presentation.xml'][$ext];
                foreach (['[Content_Types].xml', '_rels/.rels', $main] as $required) {
                    if ($zip->locateName($required) === false) {
                        throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
                    }
                }
                $typeEntry = $zip->statName('[Content_Types].xml');
                if (!$typeEntry || $typeEntry['size'] > 65536) { throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422); }
                $types = $zip->getFromName('[Content_Types].xml', 65536);
                if ($types === false || stripos($types, '<!DOCTYPE') !== false || stripos($types, '<!ENTITY') !== false
                    || stripos($types, 'macroEnabled') !== false || stripos($types, 'vbaProject') !== false
                    || simplexml_load_string($types, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING) === false) {
                    throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
                }
            } elseif (in_array($ext, ['odt', 'ods', 'odp'], true)) {
                $expected = ['odt' => 'application/vnd.oasis.opendocument.text', 'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
                    'odp' => 'application/vnd.oasis.opendocument.presentation'][$ext];
                if ($zip->getFromName('mimetype', 128) !== $expected || $zip->locateName('content.xml') === false) {
                    throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
                }
            }
        } finally {
            $zip->close();
        }
    }
}
