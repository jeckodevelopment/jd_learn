<?php

declare(strict_types=1);

namespace Jlms\Contracts\Security;

/** Server deployment injects values; extension configuration contains only names. */
final class EnvironmentSecretProvider
{
    public function resolve(string $reference): string
    {
        if (!preg_match('/\Aenv:(JLMS_SECRET_[A-Z0-9_]{1,80})\z/D', $reference, $match)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_CONFIGURATION', 503);
        }
        $secret = getenv($match[1]);
        if (!is_string($secret) || strlen($secret) < 10 || strlen($secret) > 4096 || preg_match('/[^\x21-\x7e]/', $secret)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_CONFIGURATION', 503);
        }
        return $secret;
    }
}
