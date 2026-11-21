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
    public static function storage(string $configured, string $siteRoot, string $publicRoot, string $documentRoot = ''): array
    {
        $configured = trim($configured);
        $state = 'managed';
        if ($configured !== '') {
            $path = realpath($configured);
            $site = realpath($siteRoot);
            $public = realpath($publicRoot);
            $document = $documentRoot !== '' ? realpath($documentRoot) : false;
            if ($path === false || !is_dir($path) || !is_writable($path) || is_link($configured)) {
                $state = 'unavailable';
            } elseif (($site && ($path === $site || str_starts_with($path, $site . DIRECTORY_SEPARATOR)))
                || ($public && ($path === $public || str_starts_with($path, $public . DIRECTORY_SEPARATOR)))
                || ($document && ($path === $document || str_starts_with($path, $document . DIRECTORY_SEPARATOR)))) {
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

    /**
     * Read-only projection from canonical template IDs and installed language keys.
     * No subjects, message bodies, recipient data or arbitrary template identifiers
     * are returned to the dashboard. Missing default templates require intervention.
     *
     * @param list<string> $required Canonical template IDs from MailTemplateInstaller
     * @param list<array{template_id:string, language:string}> $installed
     */
    public static function mailTemplates(array $required, array $installed): array
    {
        $available = [];
        foreach ($installed as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = $row['template_id'] ?? null;
            $language = $row['language'] ?? null;
            if (is_string($id) && is_string($language) && $language === '*') {
                $available[$id] = true;
            }
        }
        $missing = 0;
        foreach (array_unique($required) as $id) {
            if (!isset($available[$id])) {
                $missing++;
            }
        }
        $ok = $missing === 0 && count($required) > 0;
        return [
            'kind' => 'mail',
            'labelKey' => 'COM_JLMS_CONFIG_MAIL_TEMPLATES_LABEL',
            'severity' => $ok ? 'ok' : 'error',
            'stateKey' => $ok ? 'COM_JLMS_CONFIG_MAIL_TEMPLATES_READY' : 'COM_JLMS_CONFIG_MAIL_TEMPLATES_MISSING',
            'guidanceKey' => $ok ? 'COM_JLMS_CONFIG_MAIL_TEMPLATES_GUIDANCE_READY' : 'COM_JLMS_CONFIG_MAIL_TEMPLATES_GUIDANCE_MISSING',
        ];
    }

    /**
     * Fixed, allowlisted operational settings. Never include setting values, paths,
     * secrets or raw administrator inputs in the output. This is configuration
     * readiness only, not a connection/SMTP/virus-scan test.
     *
     * @param array<string, mixed> $params Component params, read only
     * @return list<array{kind:string,labelKey:string,severity:string,stateKey:string,guidanceKey:string}>
     */
    public static function operationalSettings(array $params): array
    {
        $checks = [];
        $add = static function (string $label, bool $enabled, string $on, string $off, string $guidanceOn, string $guidanceOff) use (&$checks): void {
            $checks[] = [
                'kind' => 'setting',
                'labelKey' => $label,
                'severity' => $enabled ? 'ok' : 'warning',
                'stateKey' => $enabled ? $on : $off,
                'guidanceKey' => $enabled ? $guidanceOn : $guidanceOff,
            ];
        };
        $scannerEnabled = (string) ($params['material_scan_required'] ?? '0') === '1';
        $scannerPath = $params['material_clamd_socket'] ?? '';
        $scannerConfigured = is_string($scannerPath) && str_starts_with($scannerPath, '/') && !str_contains($scannerPath, chr(0));
        if ($scannerEnabled) {
            $checks[] = [
                'kind' => 'setting',
                'labelKey' => 'COM_JLMS_CONFIG_SCAN_LABEL',
                'severity' => $scannerConfigured ? 'warning' : 'error',
                'stateKey' => $scannerConfigured ? 'COM_JLMS_CONFIG_SCAN_UNVERIFIED' : 'COM_JLMS_CONFIG_SCAN_MISSING',
                'guidanceKey' => $scannerConfigured ? 'COM_JLMS_CONFIG_SCAN_GUIDANCE_UNVERIFIED' : 'COM_JLMS_CONFIG_SCAN_GUIDANCE_MISSING',
            ];
        } else {
            $add('COM_JLMS_CONFIG_SCAN_LABEL', false,
                'COM_JLMS_CONFIG_SCAN_UNVERIFIED', 'COM_JLMS_CONFIG_SCAN_DISABLED',
                'COM_JLMS_CONFIG_SCAN_GUIDANCE_UNVERIFIED', 'COM_JLMS_CONFIG_SCAN_GUIDANCE_DISABLED');
        }
        $videoMode = (string) ($params['video_delivery_mode'] ?? 'disabled');
        // Enabled delivery does not imply that private storage and ffprobe exist.
        $add('COM_JLMS_CONFIG_VIDEO_LOCAL_LABEL', $videoMode === 'php_bounded',
            'COM_JLMS_CONFIG_VIDEO_LOCAL_ACTIVE', 'COM_JLMS_CONFIG_VIDEO_LOCAL_DISABLED',
            'COM_JLMS_CONFIG_VIDEO_LOCAL_GUIDANCE_ACTIVE', 'COM_JLMS_CONFIG_VIDEO_LOCAL_GUIDANCE_DISABLED');
        if ($videoMode === 'php_bounded') {
            $checks[array_key_last($checks)]['severity'] = 'warning';
        }
        $add('COM_JLMS_CONFIG_CERTIFICATES_LABEL', (string) ($params['certificate_enabled'] ?? '0') === '1',
            'COM_JLMS_CONFIG_CERTIFICATES_ACTIVE', 'COM_JLMS_CONFIG_CERTIFICATES_DISABLED',
            'COM_JLMS_CONFIG_CERTIFICATES_GUIDANCE_ACTIVE', 'COM_JLMS_CONFIG_CERTIFICATES_GUIDANCE_DISABLED');
        // The global flags are inherited by per-course settings. Diagnostic status
        // explains the configured default without evaluating any course/user scope.
        $mailLabels = [
            'mail_manager_request_enabled' => 'COM_JLMS_MAIL_MANAGER_REQUEST_ENABLED',
            'mail_course_update_enabled' => 'COM_JLMS_MAIL_COURSE_UPDATE_ENABLED',
            'mail_lesson_release_enabled' => 'COM_JLMS_MAIL_LESSON_RELEASE_ENABLED',
            'mail_teacher_assignment_enabled' => 'COM_JLMS_MAIL_TEACHER_ASSIGNMENT_ENABLED',
            'mail_approval_enabled' => 'COM_JLMS_MAIL_APPROVAL_ENABLED',
            'mail_rejection_enabled' => 'COM_JLMS_MAIL_REJECTION_ENABLED',
            'mail_request_enabled' => 'COM_JLMS_MAIL_REQUEST_ENABLED',
            'mail_enrollment_enabled' => 'COM_JLMS_MAIL_ENROLLMENT_ENABLED',
            'mail_completion_enabled' => 'COM_JLMS_MAIL_COMPLETION_ENABLED',
            'mail_suspension_enabled' => 'COM_JLMS_MAIL_SUSPENSION_ENABLED',
            'mail_cancellation_enabled' => 'COM_JLMS_MAIL_CANCELLATION_ENABLED',
            'mail_reactivation_enabled' => 'COM_JLMS_MAIL_REACTIVATION_ENABLED',
            'mail_certificate_enabled' => 'COM_JLMS_MAIL_CERTIFICATE_ENABLED',
            'mail_communication_enabled' => 'COM_JLMS_MAIL_COMMUNICATION_ENABLED',
            'mail_expiry_enabled' => 'COM_JLMS_MAIL_EXPIRY_ENABLED',
        ];
        $mailFlags = array_keys($mailLabels);
        $defaultOff = ['mail_manager_request_enabled', 'mail_course_update_enabled',
            'mail_lesson_release_enabled', 'mail_expiry_enabled'];
        $enabled = 0;
        foreach ($mailFlags as $key) {
            $default = in_array($key, $defaultOff, true) ? '0' : '1';
            if ((string) ($params[$key] ?? $default) === '1') {
                ++$enabled;
            }
        }
        $add('COM_JLMS_CONFIG_MAIL_FLAGS_LABEL', $enabled > 0,
            'COM_JLMS_CONFIG_MAIL_FLAGS_ACTIVE', 'COM_JLMS_CONFIG_MAIL_FLAGS_DISABLED',
            'COM_JLMS_CONFIG_MAIL_FLAGS_GUIDANCE_ACTIVE', 'COM_JLMS_CONFIG_MAIL_FLAGS_GUIDANCE_DISABLED');
        // Every event has its own localized, actionable status; the summary
        // alone must not hide an individually disabled notification.
        foreach ($mailFlags as $key) {
            $default = in_array($key, $defaultOff, true) ? '0' : '1';
            $isEnabled = (string) ($params[$key] ?? $default) === '1';
            $add($mailLabels[$key], $isEnabled,
                'COM_JLMS_CONFIG_MAIL_FLAGS_ACTIVE', 'COM_JLMS_CONFIG_MAIL_FLAGS_DISABLED',
                'COM_JLMS_CONFIG_MAIL_FLAGS_GUIDANCE_ACTIVE', 'COM_JLMS_CONFIG_MAIL_FLAGS_GUIDANCE_DISABLED');
        }
        // Catalogue presentation settings are inherited by menu items; this
        // check never implies that a menu override or remote connection was tested.
        $page = (string) ($params['catalog_page_size'] ?? '12');
        $order = (string) ($params['catalog_default_order'] ?? 'editorial');
        $valid = in_array($page, ['6', '9', '12', '24'], true)
            && in_array($order, ['editorial', 'recent', 'title', 'duration', 'published'], true);
        $add('COM_JLMS_CONFIG_CATALOG_LABEL', $valid,
            'COM_JLMS_CONFIG_CATALOG_READY', 'COM_JLMS_CONFIG_CATALOG_INVALID',
            'COM_JLMS_CONFIG_CATALOG_GUIDANCE_READY', 'COM_JLMS_CONFIG_CATALOG_GUIDANCE_INVALID');
        return $checks;
    }

    /**
     * Pure, local, read-only preflight for the two requirements of the native
     * MP4 upload workflow. No ffprobe process is ever started. The runtime
     * remains the authority for file inspection and HTTP delivery.
     *
     * @return list<array{kind:string,labelKey:string,severity:string,stateKey:string,guidanceKey:string}>
     */
    public static function localVideoPrerequisites(
        string $mode, string $root, string $probe,
        string $siteRoot, string $publicRoot, string $documentRoot = ''
    ): array {
        if ($mode !== 'php_bounded') {
            return [];
        }
        $isAbsolute = static fn (string $value): bool =>
            str_starts_with($value, '/') ||
            (strlen($value) > 2 && ctype_alpha($value[0]) && $value[1] === ':'
                && ($value[2] === '/' || $value[2] === chr(92)));
        $rootValid = $root !== '' && $isAbsolute($root);
        $storage = $rootValid
            ? self::storage($root, $siteRoot, $publicRoot, $documentRoot)
            : ['severity' => 'error'];
        $readyStorage = $storage['severity'] === 'ok';
        // VideoFilePolicy requires a Unix absolute executable and proc_open.
        // No execution, remote call or details of the binary path are exposed.
        $probeValid = $probe !== '' && str_starts_with($probe, '/')
            && is_file($probe) && !is_link($probe) && is_executable($probe)
            && function_exists('proc_open');
        return [
            [
                'kind' => 'video_storage',
                'labelKey' => 'COM_JLMS_CONFIG_VIDEO_ROOT_LABEL',
                'severity' => $readyStorage ? 'ok' : 'error',
                'stateKey' => $readyStorage ? 'COM_JLMS_CONFIG_VIDEO_ROOT_READY' : 'COM_JLMS_CONFIG_VIDEO_ROOT_UNAVAILABLE',
                'guidanceKey' => $readyStorage ? 'COM_JLMS_CONFIG_VIDEO_ROOT_GUIDANCE_READY' : 'COM_JLMS_CONFIG_VIDEO_ROOT_GUIDANCE_UNAVAILABLE',
            ],
            [
                'kind' => 'video_probe',
                'labelKey' => 'COM_JLMS_CONFIG_VIDEO_PROBE_LABEL',
                'severity' => $probeValid ? 'warning' : 'error',
                'stateKey' => $probeValid ? 'COM_JLMS_CONFIG_VIDEO_PROBE_UNVERIFIED' : 'COM_JLMS_CONFIG_VIDEO_PROBE_UNAVAILABLE',
                'guidanceKey' => $probeValid ? 'COM_JLMS_CONFIG_VIDEO_PROBE_GUIDANCE_UNVERIFIED' : 'COM_JLMS_CONFIG_VIDEO_PROBE_GUIDANCE_UNAVAILABLE',
            ],
        ];
    }

    /**
     * Diagnose effective inheritance without exposing course/menu identifiers,
     * titles, secrets or raw JSON. The caller is responsible for bounding reads;
     * incomplete scans are never described as ready. Course/menu completeness
     * is independent: overflow in one source must not downgrade the other.
     *
     * @param list<string|null> $courseParams
     * @param list<array{link:string,params:string}> $menuEntries
     */
    public static function inheritance(array $courseParams, array $menuEntries, bool $coursesComplete, bool $menusComplete): array
    {
        $courseOverrides = 0;
        $courseInvalid = 0;
        foreach ($courseParams as $raw) {
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }
            try {
                $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                ++$courseInvalid;
                continue;
            }
            if (!is_array($data) || ($data !== [] && array_is_list($data))) {
                ++$courseInvalid;
                continue;
            }
            if (!array_key_exists('mail_preferences', $data)) {
                continue;
            }
            $overrides = $data['mail_preferences'];
            if (!is_array($overrides) || ($overrides !== [] && array_is_list($overrides))) {
                ++$courseInvalid;
                continue;
            }
            foreach ($overrides as $event => $value) {
                if (!is_string($event) || !in_array($event, MailPreferences::EVENTS, true)
                    || !in_array($value, ['enabled', 'disabled', 'inherit'], true)) {
                    ++$courseInvalid;
                } elseif ($value !== 'inherit') {
                    ++$courseOverrides;
                }
            }
        }
        $menuOverrides = 0;
        $menuInvalid = 0;
        $allowedKeys = [
            'catalog_show_filters', 'catalog_show_order', 'catalog_show_category',
            'catalog_show_cover', 'catalog_show_summary',
            'catalog_page_size', 'catalog_default_order',
        ];
        foreach ($menuEntries as $menu) {
            if (!is_array($menu) || !is_string($menu['link'] ?? null)
                || !preg_match('~\\Aindex\\.php\\?option=com_jlms&view=(?:courses|category)(?:&|\\z)~D', $menu['link'])) {
                continue;
            }
            // Joomla may persist an empty menu params value for inherited defaults.
            // A blank string is equivalent to {}, not a corrupt override.
            $rawParams = $menu['params'] ?? '';
            if ($rawParams === null || (is_string($rawParams) && trim($rawParams) === '')) {
                $params = [];
            } elseif (!is_string($rawParams)) {
                ++$menuInvalid;
                continue;
            } else {
                try {
                    $params = json_decode($rawParams, true, 32, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    ++$menuInvalid;
                    continue;
                }
            }
            if (!is_array($params) || ($params !== [] && array_is_list($params))) {
                ++$menuInvalid;
                continue;
            }
            foreach ($allowedKeys as $key) {
                $value = $params[$key] ?? '';
                if ($value === '' || $value === null) {
                    continue;
                }
                $allowed = match ($key) {
                    'catalog_page_size' => ['6', '9', '12', '24'],
                    'catalog_default_order' => ['editorial', 'recent', 'title', 'duration', 'published'],
                    default => ['0', '1'],
                };
                if (in_array((string) $value, $allowed, true)) {
                    ++$menuOverrides;
                } else {
                    ++$menuInvalid;
                }
            }
        }
        $make = static function (string $label, int $overrides, int $invalid, bool $complete): array {
            // Known invalid values must remain actionable even when the bounded scan is incomplete.
            // Only successful states are downgraded to INCOMPLETE.
            $state = $invalid > 0 ? 'INVALID' : (!$complete ? 'INCOMPLETE' : ($overrides > 0 ? 'OVERRIDES' : 'INHERITED'));
            $severity = $state === 'INVALID' ? 'error' : ($state === 'INHERITED' ? 'ok' : 'warning');
            $states = [
                'INCOMPLETE' => ['COM_JLMS_CONFIG_INHERIT_INCOMPLETE', 'COM_JLMS_CONFIG_INHERIT_GUIDANCE_INCOMPLETE'],
                'INVALID' => ['COM_JLMS_CONFIG_INHERIT_INVALID', 'COM_JLMS_CONFIG_INHERIT_GUIDANCE_INVALID'],
                'OVERRIDES' => ['COM_JLMS_CONFIG_INHERIT_OVERRIDES', 'COM_JLMS_CONFIG_INHERIT_GUIDANCE_OVERRIDES'],
                'INHERITED' => ['COM_JLMS_CONFIG_INHERIT_INHERITED', 'COM_JLMS_CONFIG_INHERIT_GUIDANCE_INHERITED'],
            ];
            return [
                'kind' => 'inheritance',
                'labelKey' => $label,
                'severity' => $severity,
                'stateKey' => $states[$state][0],
                'guidanceKey' => $states[$state][1],
            ];
        };
        return [
            $make('COM_JLMS_CONFIG_INHERIT_COURSE_LABEL', $courseOverrides, $courseInvalid, $coursesComplete),
            $make('COM_JLMS_CONFIG_INHERIT_MENU_LABEL', $menuOverrides, $menuInvalid, $menusComplete),
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
