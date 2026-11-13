<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

/** Global and per-course certificate switches. The safe default is disabled. */
final class CertificatePreferences
{
    public static function merge(array $input, ?string $params): ?string
    {
        if (!array_key_exists('certificate_enabled', $input)) { return null; }
        $value = $input['certificate_enabled'];
        if (!is_string($value) || !in_array($value, ['inherit', 'enabled', 'disabled'], true)) {
            throw new \InvalidArgumentException('COM_JLMS_CERTIFICATE_PREFERENCE_INVALID');
        }
        $data = json_decode($params ?: '{}', true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) { throw new \InvalidArgumentException('COM_JLMS_CERTIFICATE_PREFERENCE_INVALID'); }
        if ($value === 'inherit') { unset($data['certificate_enabled']); }
        else { $data['certificate_enabled'] = $value; }
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public static function enabled(bool $globalEnabled, ?string $courseParams): bool
    {
        $data = json_decode($courseParams ?: '{}', true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) { return false; }
        return match ($data['certificate_enabled'] ?? 'inherit') {
            'enabled' => true,
            'disabled' => false,
            'inherit' => $globalEnabled,
            default => false,
        };
    }
}
