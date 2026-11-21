<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Jlms\Contracts\Video\ProviderDescriptor;

/** Read-only, non-secret status projection for bundled and registered video plugins. */
final class DashboardProviderStatus
{
    private const BUNDLED = [
        'youtube' => 'COM_JLMS_PROVIDER_YOUTUBE',
        'vimeo' => 'COM_JLMS_PROVIDER_VIMEO',
        'cloudflarestream' => 'COM_JLMS_PROVIDER_CLOUDFLARESTREAM',
        'html5' => 'COM_JLMS_PROVIDER_HTML5',
    ];

    /**
     * SCR-16: inspect only Joomla's locally persisted extension metadata.
     * NEVER register/dispatch providers during diagnostics: third-party event
     * handlers may perform network requests or writes even for status checks.
     *
     * @param array<string,object> $extensions
     * @param array<string,mixed> $componentSettings
     */
    public static function inspectLocal(array $extensions, array $componentSettings): array
    {
        $descriptors = [];
        foreach (['youtube', 'vimeo', 'html5', 'cloudflarestream'] as $key) {
            $extension = $extensions[$key] ?? null;
            if (!$extension) {
                continue;
            }
            $rawParams = $extension->params ?? '{}';
            $params = is_string($rawParams) ? json_decode($rawParams, true, 32) : null;
            $params = is_array($params) && ($params === [] || !array_is_list($params)) ? $params : [];
            $enabled = (string) ($params['playback_enabled'] ?? '0') === '1';
            if ($key === 'html5') {
                $enabled = $enabled
                    && (string) ($componentSettings['video_storage_root'] ?? '') !== ''
                    && in_array((string) ($componentSettings['video_delivery_mode'] ?? 'disabled'),
                        ['php_bounded', 'x_accel'], true);
            }
            // Cloudflare and arbitrary third-party providers cannot be certified
            // locally: a present token reference is not proof of connectivity.
            $descriptors[] = new ProviderDescriptor($key, self::BUNDLED[$key], 1,
                $key !== 'cloudflarestream' && $enabled);
        }
        $projection = self::summarize($extensions, $descriptors);
        foreach ($projection as &$provider) {
            $key = $provider['key'];
            $extension = $extensions[$key] ?? null;
            if (!$extension || (int) ($extension->enabled ?? 0) !== 1) {
                continue;
            }
            $unknown = !array_key_exists($key, self::BUNDLED);
            if ($unknown || $key === 'cloudflarestream') {
                $rawParams = $extension->params ?? '{}';
                $params = is_string($rawParams) ? json_decode($rawParams, true, 32) : null;
                $enabled = is_array($params) && (string) ($params['playback_enabled'] ?? '0') === '1';
                if ($unknown || $enabled) {
                    $provider['stateKey'] = 'COM_JLMS_PROVIDER_UNVERIFIED';
                    $provider['stateClass'] = 'warning text-dark';
                }
            }
        }
        unset($provider);
        return $projection;
    }

    /** Read-only, safe next step for the state already projected by summarize(). */
    public static function guidanceKey(string $stateKey): string
    {
        return match ($stateKey) {
            'COM_JLMS_PROVIDER_NOT_INSTALLED' => 'COM_JLMS_PROVIDER_GUIDANCE_NOT_INSTALLED',
            'COM_JLMS_PROVIDER_DISABLED' => 'COM_JLMS_PROVIDER_GUIDANCE_DISABLED',
            'COM_JLMS_PROVIDER_UNAVAILABLE' => 'COM_JLMS_PROVIDER_GUIDANCE_UNAVAILABLE',
            'COM_JLMS_PROVIDER_UNVERIFIED' => 'COM_JLMS_PROVIDER_GUIDANCE_UNVERIFIED',
            'COM_JLMS_NOT_CONFIGURED' => 'COM_JLMS_PROVIDER_GUIDANCE_NOT_CONFIGURED',
            'COM_JLMS_READY' => 'COM_JLMS_PROVIDER_GUIDANCE_READY',
            default => 'COM_JLMS_PROVIDER_GUIDANCE_UNKNOWN',
        };
    }

    public static function summarize(array $extensions, array $descriptors): array
    {
        $descriptorMap = [];
        foreach ($descriptors as $descriptor) {
            if ($descriptor instanceof ProviderDescriptor) {
                $descriptorMap[$descriptor->key] = $descriptor;
            }
        }

        $keys = array_values(array_unique(array_merge(array_keys(self::BUNDLED), array_keys($extensions))));
        sort($keys);
        // Keep the bundled providers first in the same stable order as their user-facing importance.
        $keys = array_values(array_unique(array_merge(array_keys(self::BUNDLED), $keys)));
        $providers = [];
        foreach ($keys as $key) {
            // Provider identities are extension-controlled: never render a raw
            // key that could contain credentials, markup or sensitive data.
            if (!is_string($key) || !preg_match('/\\A[a-z][a-z0-9_]{0,63}\\z/D', $key)) {
                continue;
            }
            $extension = $extensions[$key] ?? null;
            $descriptor = $descriptorMap[$key] ?? null;
            if (!$extension) {
                $state = 'COM_JLMS_PROVIDER_NOT_INSTALLED';
                $class = 'secondary';
            } elseif ((int) ($extension->enabled ?? 0) !== 1) {
                $state = 'COM_JLMS_PROVIDER_DISABLED';
                $class = 'secondary';
            } elseif (!$descriptor) {
                $state = 'COM_JLMS_PROVIDER_UNAVAILABLE';
                $class = 'danger';
            } elseif (!$descriptor->configured) {
                $state = 'COM_JLMS_NOT_CONFIGURED';
                $class = 'warning text-dark';
            } else {
                $state = 'COM_JLMS_READY';
                $class = 'success';
            }

            $candidateLabel = self::BUNDLED[$key] ?? $descriptor?->labelKey ?? ($extension->name ?? null);
            // Joomla Text::_() must only receive localized identifiers from
            // untrusted provider descriptors, never arbitrary plugin contents.
            $safeLabel = is_string($candidateLabel)
                && preg_match('/\\A(?:COM_JLMS|PLG_JLMSVIDEO)_[A-Z0-9_]+\\z/D', $candidateLabel)
                ? $candidateLabel : 'COM_JLMS_CONFIG_DIAGNOSTICS_UNKNOWN_LABEL';
            $providers[] = [
                'key' => $key,
                'labelKey' => $safeLabel,
                'stateKey' => $state,
                'stateClass' => $class,
            ];
        }

        return $providers;
    }
}
