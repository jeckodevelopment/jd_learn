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

            $providers[] = [
                'key' => $key,
                'labelKey' => self::BUNDLED[$key] ?? $descriptor?->labelKey ?? (string) ($extension->name ?? $key),
                'stateKey' => $state,
                'stateClass' => $class,
            ];
        }

        return $providers;
    }
}
