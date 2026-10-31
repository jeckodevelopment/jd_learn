<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class ValidationIssue
{
    public function __construct(public string $code, public string $messageKey, public string $field)
    {
    }
}
