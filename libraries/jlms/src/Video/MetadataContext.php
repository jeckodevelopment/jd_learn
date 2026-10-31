<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class MetadataContext
{
    public function __construct(public int $userId, public string $locale, public bool $allowRemote)
    {
    }
}
