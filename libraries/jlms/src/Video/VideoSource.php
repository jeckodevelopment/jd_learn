<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class VideoSource
{
    /** @param array<string, scalar|null> $options Secret references are not allowed here. */
    public function __construct(
        public string $providerKey,
        public string $reference,
        public int $revision,
        public array $options = [],
    ) {
    }
}
