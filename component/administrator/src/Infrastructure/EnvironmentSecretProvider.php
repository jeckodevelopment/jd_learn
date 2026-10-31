<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Jlms\Contracts\Video\SecretProviderInterface;

final class EnvironmentSecretProvider implements SecretProviderInterface
{
    public function get(string $reference): string
    {
        if (!preg_match('/^ENV:([A-Z][A-Z0-9_]{0,127})$/D', $reference, $match)) {
            throw new \DomainException('INVALID_SECRET_REFERENCE');
        }
        $value = getenv($match[1]);
        if ($value === false || $value === '') {
            throw new \DomainException('SECRET_UNAVAILABLE');
        }
        return $value;
    }
}
