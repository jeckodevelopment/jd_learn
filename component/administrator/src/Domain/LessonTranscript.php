<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

/** Sanitization and version-scoped storage for optional lesson transcripts. */
final class LessonTranscript
{
    public const MAX_BYTES = 262144;

    public static function sanitizeInput(mixed $value, callable $filter): string
    {
        if (!is_string($value) || strlen($value) > self::MAX_BYTES) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
        }

        return $filter($value);
    }

    public static function encode(string $value, string $kind): string
    {
        $key = self::key($kind);

        return json_encode([$key => $value], JSON_THROW_ON_ERROR);
    }

    public static function decode(string $kind, string $paramsJson): string
    {
        if (!in_array($kind, ['audio', 'video'], true)) {
            return '';
        }

        $params = json_decode($paramsJson, true, 16, JSON_THROW_ON_ERROR);
        $key = self::key($kind);

        return is_array($params) && is_string($params[$key] ?? null) ? $params[$key] : '';
    }

    private static function key(string $kind): string
    {
        return match ($kind) {
            'audio' => 'audio_transcript',
            'video' => 'video_transcript',
            default => throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT'),
        };
    }
}
