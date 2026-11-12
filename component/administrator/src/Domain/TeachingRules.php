<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

/** Supported programme rules; thresholds use exact hundredths of a percent. */
final class TeachingRules
{
    public static function decode(string $json): array
    {
        try { $rules = json_decode($json, true, 32, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
        if (!is_array($rules)) { throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED'); }
        $threshold = $rules['video_threshold_basis_points'] ?? 9000;
        $mode = $rules['mode'] ?? 'learning';
        $completionPolicy = $rules['completion_policy'] ?? '';
        if (($rules['_schema'] ?? 1) !== 1 || !in_array($mode, ['learning', 'consultation'], true)
            || !in_array(($rules['sequence_mode'] ?? ''), ['free', 'sequential'], true)
            || ($rules['certificate_enabled'] ?? true) !== false
            || ($mode === 'learning' && $completionPolicy !== 'all_required')
            || ($mode === 'consultation' && ($completionPolicy !== 'none' || ($rules['sequence_mode'] ?? '') !== 'free'))
            || !is_int($threshold) || $threshold < 1 || $threshold > 10000) {
            throw new \DomainException('COM_JLMS_PUBLICATION_UNSUPPORTED');
        }
        $rules['video_threshold_basis_points'] = $threshold;
        return $rules;
    }

    public static function basisPoints(mixed $value): int
    {
        if ((!is_string($value) && !is_int($value))
            || preg_match('/\A([0-9]{1,3})(?:\.([0-9]{1,2}))?\z/D', (string)$value, $parts) !== 1) {
            throw new \DomainException('COM_JLMS_TEACHING_THRESHOLD_INVALID');
        }
        $result = (int)$parts[1] * 100 + (int)str_pad($parts[2] ?? '', 2, '0');
        if ($result < 1 || $result > 10000) { throw new \DomainException('COM_JLMS_TEACHING_THRESHOLD_INVALID'); }
        return $result;
    }

    public static function percent(int $basisPoints): string
    {
        return rtrim(rtrim(sprintf('%d.%02d', intdiv($basisPoints, 100), $basisPoints % 100), '0'), '.');
    }
}
