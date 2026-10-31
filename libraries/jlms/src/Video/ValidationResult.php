<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class ValidationResult
{
    /** @param list<ValidationIssue> $errors @param list<ValidationIssue> $warnings */
    public function __construct(public array $errors = [], public array $warnings = [])
    {
    }
}
