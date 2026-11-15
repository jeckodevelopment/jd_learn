<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

/** Sanitization and version-scoped storage for optional audio lesson transcripts. */
final class AudioTranscript
{
    public const MAX_BYTES = 262144;

    public static function sanitizeInput(mixed $value, callable $filter): string
    {
        if (!is_string($value) || strlen($value) > self::MAX_BYTES) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
        }

        return $filter($value);
    }

    public static function encode(string $value): string
    {
        return json_encode(['audio_transcript' => $value], JSON_THROW_ON_ERROR);
    }

    public static function decode(string $kind, string $paramsJson): string
    {
        if ($kind !== 'audio') {
            return '';
        }

        $params = json_decode($paramsJson, true, 16, JSON_THROW_ON_ERROR);

        return is_array($params) && is_string($params['audio_transcript'] ?? null)
            ? $params['audio_transcript']
            : '';
    }
}
