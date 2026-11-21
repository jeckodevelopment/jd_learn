<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

/**
 * DOC-07: safe, read-only configuration diagnostics from existing local
 * scheduler/provider projections. Never probe a remote provider or expose
 * credentials, filesystem paths, queue payloads or database errors.
 */
final class ConfigurationDiagnostics
{
    private const SCHEDULER_ERRORS = ['missing', 'invalid_rules', 'duplicates', 'failed'];
    private const SCHEDULER_WARNINGS = ['disabled', 'overdue', 'unknown', 'waiting'];
    private const PROVIDER_ERRORS = ['COM_JLMS_PROVIDER_UNAVAILABLE'];
    private const PROVIDER_WARNINGS = [
        'COM_JLMS_PROVIDER_NOT_INSTALLED', 'COM_JLMS_PROVIDER_DISABLED',
        'COM_JLMS_NOT_CONFIGURED',
    ];

    public static function summarize(array $scheduler, array $providers): array
    {
        $checks = [];
        foreach ($scheduler as $entry) {
            $state = (string) ($entry['state'] ?? 'unknown');
            $duplicates = (bool) ($entry['duplicates'] ?? false);
            $severity = $duplicates || in_array($state, self::SCHEDULER_ERRORS, true)
                ? 'error' : (in_array($state, self::SCHEDULER_WARNINGS, true) ? 'warning' : 'ok');
            if ($state !== 'operational' && $severity === 'ok') {
                $severity = 'warning';
            }
            $checks[] = [
                'kind' => 'scheduler',
                'labelKey' => self::safeLabel($entry['label'] ?? null),
                'severity' => $severity,
                'guidanceKey' => \Jlms\Component\Jlms\Administrator\Infrastructure\SchedulerHealth::guidanceKey($state, $duplicates),
                'stateKey' => self::schedulerStateKey($state),
            ];
        }
        foreach ($providers as $entry) {
            $state = (string) ($entry['stateKey'] ?? '');
            $severity = in_array($state, self::PROVIDER_ERRORS, true) ? 'error'
                : ($state === 'COM_JLMS_READY' ? 'ok' : 'warning');
            $checks[] = [
                'kind' => 'video',
                'labelKey' => self::safeLabel($entry['label'] ?? null),
                'severity' => $severity,
                'guidanceKey' => DashboardProviderStatus::guidanceKey($state),
                'stateKey' => in_array($state, [
                    'COM_JLMS_PROVIDER_NOT_INSTALLED', 'COM_JLMS_PROVIDER_DISABLED',
                    'COM_JLMS_PROVIDER_UNAVAILABLE', 'COM_JLMS_NOT_CONFIGURED', 'COM_JLMS_READY',
                ], true) ? $state : 'COM_JLMS_PROVIDER_GUIDANCE_UNKNOWN',
            ];
        }
        return $checks;
    }

    /**
     * Local-only storage preflight. No directory creation or network probes.
     * Managed storage is deliberately not reported as ready before runtime checks.
     * Never include the configured path in the diagnostic result.
     */
    public static function storage(string $configured, string $siteRoot, string $publicRoot): array
    {
        $configured = trim($configured);
        $state = 'managed';
        if ($configured !== '') {
            $path = realpath($configured);
            $site = realpath($siteRoot);
            $public = realpath($publicRoot);
            if ($path === false || !is_dir($path) || !is_writable($path) || is_link($configured)) {
                $state = 'unavailable';
            } elseif (($site && ($path === $site || str_starts_with($path, $site . DIRECTORY_SEPARATOR)))
                || ($public && ($path === $public || str_starts_with($path, $public . DIRECTORY_SEPARATOR)))) {
                $state = 'public';
            } else {
                $state = 'private';
            }
        }
        $keys = match ($state) {
            'private' => ['ok', 'COM_JLMS_CONFIG_STORAGE_PRIVATE', 'COM_JLMS_CONFIG_STORAGE_GUIDANCE_PRIVATE'],
            'public' => ['error', 'COM_JLMS_CONFIG_STORAGE_PUBLIC', 'COM_JLMS_CONFIG_STORAGE_GUIDANCE_PUBLIC'],
            'unavailable' => ['error', 'COM_JLMS_CONFIG_STORAGE_UNAVAILABLE', 'COM_JLMS_CONFIG_STORAGE_GUIDANCE_UNAVAILABLE'],
            default => ['warning', 'COM_JLMS_CONFIG_STORAGE_MANAGED', 'COM_JLMS_CONFIG_STORAGE_GUIDANCE_MANAGED'],
        };
        return [
            'kind' => 'storage',
            'labelKey' => 'COM_JLMS_CONFIG_STORAGE_LABEL',
            'severity' => $keys[0],
            'stateKey' => $keys[1],
            'guidanceKey' => $keys[2],
        ];
    }

    /** Treat labels as translations, never as arbitrary provider-supplied content. */
    private static function safeLabel(mixed $candidate): string
    {
        return is_string($candidate) && preg_match('/\\ACOM_JLMS_[A-Z0-9_]+\\z/D', $candidate)
            ? $candidate : 'COM_JLMS_CONFIG_DIAGNOSTICS_UNKNOWN_LABEL';
    }

    private static function schedulerStateKey(string $state): string
    {
        return match ($state) {
            'missing' => 'COM_JLMS_SCHEDULER_HEALTH_MISSING',
            'invalid_rules' => 'COM_JLMS_SCHEDULER_HEALTH_INVALID_RULES',
            'disabled' => 'COM_JLMS_SCHEDULER_HEALTH_DISABLED',
            'duplicates' => 'COM_JLMS_SCHEDULER_HEALTH_DUPLICATES',
            'failed' => 'COM_JLMS_SCHEDULER_HEALTH_FAILED',
            'overdue' => 'COM_JLMS_SCHEDULER_HEALTH_OVERDUE',
            'waiting' => 'COM_JLMS_SCHEDULER_HEALTH_WAITING',
            'operational' => 'COM_JLMS_SCHEDULER_HEALTH_OPERATIONAL',
            default => 'COM_JLMS_SCHEDULER_HEALTH_UNKNOWN',
        };
    }
}
