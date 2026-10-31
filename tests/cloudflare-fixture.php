<?php

declare(strict_types=1);

require_once __DIR__ . '/../plugins/jlmsvideo/cloudflarestream/src/Provider/Provider.php';

/** No live credentials, no outbound HTTP. Test transport is injected only in disposable fixtures. */
function cloudflareFixture(): array
{
    $state = (object) ['calls' => [], 'protected' => true, 'ready' => true, 'origins' => ['school.example'],
        'status' => 200, 'tokenMode' => 'valid', 'callback' => null];
    $config = ['playback_enabled' => 1, 'account_id' => str_repeat('a', 32), 'playback_domain' => 'customer-ci.cloudflarestream.com',
        'site_host' => 'school.example', 'api_token_ref' => 'env:JLMS_SECRET_CI_STREAM', 'token_ttl_seconds' => 60, 'renew_before_seconds' => 20];
    $secret = static fn (string $reference): string => 'ci-disposable-not-a-real-secret';
    $transport = static function (string $method, string $url, array $headers, ?string $body) use ($state): array {
        $state->calls[] = ['method' => $method, 'url' => $url, 'body' => $body];
        if ($state->callback) { ($state->callback)($method); }
        $uid = str_repeat('b', 32);
        $result = ['uid' => $uid, 'requireSignedURLs' => $state->protected, 'readyToStream' => $state->ready,
            'status' => ['state' => 'ready'], 'duration' => 10, 'allowedOrigins' => $state->origins,
            'playback' => ['hls' => 'https://customer-ci.cloudflarestream.com/' . $uid . '/manifest/video.m3u8']];
        if ($method === 'POST') {
            $request = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
            $header = ['alg' => $state->tokenMode === 'none' ? 'none' : 'RS256', 'kid' => 'fixture'];
            $claims = ['sub' => $state->tokenMode === 'foreign' ? str_repeat('c',32) : $uid,
                'exp' => $request['exp'] + ($state->tokenMode === 'long' ? 100 : 0)];
            if ($state->tokenMode === 'download') { $claims['downloadable'] = true; }
            $encode = static fn (array $data): string => rtrim(strtr(base64_encode(json_encode($data, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
            $result = ['token' => $encode($header) . '.' . $encode($claims) . '.c2lnbmF0dXJl'];
        }
        return ['status' => $state->status, 'body' => json_encode(['success' => true, 'result' => $result], JSON_THROW_ON_ERROR)];
    };
    return [$config, $secret, $transport, $state];
}
