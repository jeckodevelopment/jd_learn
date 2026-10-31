<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Cloudflarestream\Provider;

use Jlms\Contracts\Video\AbstractFoundationProvider;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Contracts\Video\VideoCapabilities;
use Jlms\Contracts\Video\VideoSource;
use Jlms\Contracts\Video\SourceInput;
use Jlms\Contracts\Video\PlaybackContext;
use Jlms\Contracts\Video\PlaybackDescriptor;
use Jlms\Contracts\Video\MetadataContext;
use Jlms\Contracts\Video\VideoMetadata;

/** API signing only. Transport and secret resolution are injected, never author-controlled URLs. */
final class Provider extends AbstractFoundationProvider
{
    public function __construct(private readonly array $config = [], private readonly ?\Closure $secrets = null,
        private readonly ?\Closure $transport = null)
    {
    }

    protected function key(): string { return 'cloudflarestream'; }

    private function configuration(): array
    {
        $c = $this->config;
        if ((int) ($c['playback_enabled'] ?? 0) !== 1 || ($c['signing_mode'] ?? 'api_endpoint') !== 'api_endpoint'
            || !preg_match('/\A[a-f0-9]{32}\z/D', (string) ($c['account_id'] ?? ''))
            || !preg_match('/\Acustomer-[a-z0-9]{1,80}\.cloudflarestream\.com\z/D', (string) ($c['playback_domain'] ?? ''))
            || !preg_match('/\A(?=.{1,253}\z)[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\z/D', (string) ($c['site_host'] ?? ''))
            || str_contains($c['site_host'], '..') || !$this->secrets || !$this->transport) {
            throw new \RuntimeException('COM_JLMS_VIDEO_CONFIGURATION', 503);
        }
        $ttl = filter_var($c['token_ttl_seconds'] ?? 900, FILTER_VALIDATE_INT);
        $renew = filter_var($c['renew_before_seconds'] ?? 120, FILTER_VALIDATE_INT);
        if ($ttl === false || $renew === false || $ttl < 60 || $ttl > 3600 || $renew < 1 || $renew >= $ttl) {
            throw new \RuntimeException('COM_JLMS_VIDEO_CONFIGURATION', 503);
        }
        $c['ttl'] = $ttl; $c['renew'] = $renew;
        return $c;
    }

    public function getDescriptor(): ProviderDescriptor
    {
        try { $this->configuration(); ($this->secrets)((string) ($this->config['api_token_ref'] ?? '')); $configured = true; }
        catch (\Throwable $error) { $configured = false; }
        return new ProviderDescriptor($this->key(), 'PLG_JLMSVIDEO_CLOUDFLARESTREAM', 1, $configured);
    }

    public function getCapabilities(VideoSource $source): VideoCapabilities
    {
        $ready = $this->getDescriptor()->configured;
        return new VideoCapabilities($ready, $ready, $ready, false, false, false, $ready, false);
    }

    private function request(string $method, string $uid, ?array $body = null): array
    {
        $c = $this->configuration();
        $this->normaliseSource(new SourceInput($uid));
        $secret = ($this->secrets)((string) ($c['api_token_ref'] ?? ''));
        $url = 'https://api.cloudflare.com/client/v4/accounts/' . $c['account_id'] . '/stream/' . $uid . ($method === 'POST' ? '/token' : '');
        try { $response = ($this->transport)($method, $url, ['Authorization' => 'Bearer ' . $secret, 'Content-Type' => 'application/json'],
            $body === null ? null : json_encode($body, JSON_THROW_ON_ERROR)); }
        catch (\Throwable $error) { throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503); }
        // Never include provider response bodies, credentials or token URLs in exceptions/logs.
        if (!is_array($response) || !is_int($response['status'] ?? null) || !is_string($response['body'] ?? null)
            || strlen($response['body']) > 262144) { throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503); }
        if (in_array($response['status'], [401,403], true)) { throw new \RuntimeException('COM_JLMS_VIDEO_CREDENTIAL_REJECTED', 503); }
        if ($response['status'] === 404) { throw new \RuntimeException('COM_JLMS_VIDEO_REMOTE_NOT_FOUND', 503); }
        if ($response['status'] === 429) { throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_BUSY', 503); }
        if ($response['status'] !== 200) { throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503); }
        try { $data = json_decode($response['body'], true, 16, JSON_THROW_ON_ERROR); }
        catch (\JsonException $error) { throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503); }
        if (($data['success'] ?? false) !== true || !is_array($data['result'] ?? null)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503);
        }
        return $data['result'];
    }

    public function fetchMetadata(VideoSource $source, MetadataContext $context): VideoMetadata
    {
        if (!$context->allowRemote || $context->userId < 1 || $source->providerKey !== $this->key()) {
            throw new \RuntimeException('COM_JLMS_VIDEO_UNAVAILABLE', 503);
        }
        $c = $this->configuration(); $data = $this->request('GET', $source->reference);
        if (($data['uid'] ?? '') !== $source->reference || ($data['requireSignedURLs'] ?? null) !== true) {
            throw new \RuntimeException('COM_JLMS_VIDEO_REMOTE_UNPROTECTED', 503);
        }
        if (($data['readyToStream'] ?? false) !== true || ($data['status']['state'] ?? '') !== 'ready'
            || (!is_int($data['duration'] ?? null) && !is_float($data['duration'] ?? null))
            || !is_finite((float) $data['duration']) || $data['duration'] < 1 || $data['duration'] > 28800) {
            throw new \RuntimeException('COM_JLMS_VIDEO_REMOTE_NOT_READY', 503);
        }
        $url = parse_url((string) ($data['playback']['hls'] ?? ''));
        if (!$url || ($url['scheme'] ?? '') !== 'https' || ($url['host'] ?? '') !== $c['playback_domain']
            || isset($url['user']) || isset($url['pass']) || isset($url['port'])
            || ($url['path'] ?? '') !== '/' . $source->reference . '/manifest/video.m3u8') {
            throw new \RuntimeException('COM_JLMS_VIDEO_CONFIGURATION', 503);
        }
        // Explicit exact hostname restriction; no wildcard/public origin configuration is accepted here.
        $origins = $data['allowedOrigins'] ?? null;
        if (!is_array($origins) || !$origins || in_array('*', $origins, true) || !in_array($c['site_host'], $origins, true)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_ORIGIN_REJECTED', 503);
        }
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return new VideoMetadata(null, (int) round($data['duration'] * 1000), null, 'ready', $now, $now);
    }

    private static function decode(string $part): array
    {
        $raw = base64_decode(strtr($part, '-_', '+/'), true);
        try { $data = $raw === false ? null : json_decode($raw, true, 16, JSON_THROW_ON_ERROR); }
        catch (\JsonException $error) { $data = null; }
        if (!is_array($data)) { throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503); }
        return $data;
    }

    public function createPlaybackDescriptor(VideoSource $source, PlaybackContext $context): PlaybackDescriptor
    {
        if ($context->userId < 1 || $context->enrollmentId < 1 || $context->lessonId < 1 || $context->authorisationGrant === '') {
            throw new \RuntimeException('COM_JLMS_VIDEO_UNAVAILABLE', 503);
        }
        $this->fetchMetadata($source, new MetadataContext($context->userId, $context->locale, true));
        $c = $this->configuration(); $now = $context->now->getTimestamp();
        $expiry = min($now + $c['ttl'], $context->accessExpiresAt?->getTimestamp() ?? PHP_INT_MAX);
        if ($expiry <= $now + 5) { throw new \RuntimeException('COM_JLMS_LEARNING_WINDOW', 403); }
        $data = $this->request('POST', $source->reference, ['exp' => $expiry, 'downloadable' => false]);
        $token = $data['token'] ?? null;
        if (!is_string($token) || strlen($token) > 16384 || !preg_match('/\A([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]+)\z/D', $token, $parts)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503);
        }
        $header = self::decode($parts[1]); $claims = self::decode($parts[2]);
        if (($header['alg'] ?? '') !== 'RS256' || ($claims['sub'] ?? '') !== $source->reference || !is_int($claims['exp'] ?? null)
            || $claims['exp'] > $expiry || $claims['exp'] <= $now + 5 || (isset($claims['downloadable']) && $claims['downloadable'] !== false)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_PROVIDER_UNAVAILABLE', 503);
        }
        // Tokens are trusted only as responses from the fixed, TLS-verified Cloudflare API, never client JWT input.
        $expiry = $claims['exp'];
        $options = ['domain' => $c['playback_domain'], 'token' => $token, 'autoplay' => false,
            'renew_after_seconds' => max(1, $expiry - $now - min($c['renew'], intdiv($expiry - $now, 2)))];
        return new PlaybackDescriptor($this->key(), $source->revision, $this->getFrontendAdapterAsset(), $options,
            $this->getCapabilities($source), (new \DateTimeImmutable('@' . $expiry))->setTimezone(new \DateTimeZone('UTC')));
    }
}
