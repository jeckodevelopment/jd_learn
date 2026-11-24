<?php

declare(strict_types=1);

use Jlms\Plugin\Jlmsvideo\Cloudflarestream\Provider\Provider;
use Jlms\Contracts\Video\VideoSource;
use Jlms\Contracts\Video\PlaybackContext;
use Jlms\Contracts\Video\MetadataContext;

function cfExpect(bool $value, string $message): void
{
    global $checks;
    if (!$value) {
        throw new \RuntimeException($message);
    } $checks++;
}

function cfDenied(callable $work, string $key): void
{
    try {
        $work();
    } catch (\RuntimeException $error) {
        cfExpect($error->getMessage() === $key, $key . ': received ' . $error->getMessage());
        return;
    }
    throw new \RuntimeException('Expected: ' . $key);
}
