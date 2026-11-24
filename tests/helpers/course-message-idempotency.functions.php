<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Application\CourseMessageMutation;

function mail08Assert(bool $condition, string $error): void
{
    if (!$condition) {
        throw new RuntimeException($error);
    }
}
