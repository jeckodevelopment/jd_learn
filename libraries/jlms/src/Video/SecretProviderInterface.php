<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

interface SecretProviderInterface
{
    /** Only configuration references; throws on missing/inaccessible value. Never log output. */
    public function get(string $reference): string;
}
