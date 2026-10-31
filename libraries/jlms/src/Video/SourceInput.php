<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class SourceInput
{
    /** @param array<string, scalar|null> $options Validated allowlist, never arbitrary embed HTML. */
    public function __construct(public string $reference, public array $options = [])
    {
    }
}
