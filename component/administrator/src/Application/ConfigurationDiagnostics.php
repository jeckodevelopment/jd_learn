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
                'labelKey' => (string) ($entry['label'] ?? 'COM_JLMS_SCHEDULER_HEALTH_UNKNOWN'),
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
                'labelKey' => (string) ($entry['labelKey'] ?? 'COM_JLMS_PROVIDER_GUIDANCE_UNKNOWN'),
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
