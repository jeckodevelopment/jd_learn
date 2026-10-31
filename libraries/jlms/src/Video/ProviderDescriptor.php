<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class ProviderDescriptor
{
    public function __construct(
        public string $key,
        public string $labelKey,
        public int $contractMajor,
        public bool $configured,
    ) {
    }
}
