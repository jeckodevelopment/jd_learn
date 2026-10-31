<?php

declare(strict_types=1);


namespace Jlms\Contracts\Video;

final class SourceNormalizer
{
    public static function normalise(string $provider, SourceInput $input): VideoSource
    {
        $value = trim($input->reference);
        if (strlen($value) > 2048 || $value === '') {
            throw new \InvalidArgumentException('SOURCE_INVALID');
        }
        $options = [];
        $parts = str_contains($value, '://') ? parse_url($value) : false;
        if ($parts !== false) {
            if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user'], $parts['pass'])
                || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
                throw new \InvalidArgumentException('SOURCE_INVALID');
            }
            $host = strtolower($parts['host'] ?? '');
            $path = $parts['path'] ?? '/';
            parse_str($parts['query'] ?? '', $query);
            if ($provider === 'youtube') {
                if ($host === 'youtu.be' && preg_match('~^/([A-Za-z0-9_-]{11})/?$~D', $path, $m)) {
                    $value = $m[1];
                } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'www.youtube-nocookie.com'], true)) {
                    if ($path === '/watch' && is_string($query['v'] ?? null)) {
                        $value = $query['v'];
                    } elseif (preg_match('~^/embed/([A-Za-z0-9_-]{11})/?$~D', $path, $m)) {
                        $value = $m[1];
                    } else {
                        throw new \InvalidArgumentException('SOURCE_INVALID');
                    }
                } else {
                    throw new \InvalidArgumentException('SOURCE_INVALID');
                }
            } elseif ($provider === 'vimeo') {
                if (!in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)
                    || !preg_match('~^/(?:video/)?([1-9][0-9]{0,18})(?:/([A-Za-z0-9_-]{1,256}))?/?$~D', $path, $m)) {
                    throw new \InvalidArgumentException('SOURCE_INVALID');
                }
                $value = $m[1];
                $hash = $query['h'] ?? ($m[2] ?? null);
                if ($hash !== null) {
                    $options['privacy_hash'] = $hash;
                }
            } elseif ($provider === 'cloudflarestream') {
                if (!in_array($host, ['videodelivery.net', 'iframe.videodelivery.net'], true)
                    && !preg_match('/^customer-[a-z0-9]+\.cloudflarestream\.com$/D', $host)) {
                    throw new \InvalidArgumentException('SOURCE_INVALID');
                }
                if (!preg_match('~^/([a-f0-9]{32})(?:/iframe)?/?$~D', $path, $m)) {
                    throw new \InvalidArgumentException('SOURCE_INVALID');
                }
                $value = $m[1];
            } else {
                throw new \InvalidArgumentException('SOURCE_INVALID');
            }
        }
        if ($provider === 'vimeo' && isset($input->options['privacy_hash'])) {
            $options['privacy_hash'] = $input->options['privacy_hash'];
        }
        $valid = match ($provider) {
            'youtube' => preg_match('/^[A-Za-z0-9_-]{11}$/D', $value),
            'vimeo' => preg_match('/^[1-9][0-9]{0,18}$/D', $value),
            'cloudflarestream' => preg_match('/^[a-f0-9]{32}$/D', $value),
            'html5' => preg_match('/^[1-9][0-9]{0,18}$/D', $value)
                && (strlen($value) < strlen((string) PHP_INT_MAX)
                    || (strlen($value) === strlen((string) PHP_INT_MAX) && strcmp($value, (string) PHP_INT_MAX) <= 0)),
            default => false,
        };
        if (!$valid || (isset($options['privacy_hash'])
            && (!is_string($options['privacy_hash']) || !preg_match('/^[A-Za-z0-9_-]{1,256}$/D', $options['privacy_hash'])))) {
            throw new \InvalidArgumentException('SOURCE_INVALID');
        }
        return new VideoSource($provider, $value, 1, $options);
    }
}
