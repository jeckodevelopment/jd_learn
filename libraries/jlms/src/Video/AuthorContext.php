<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class AuthorContext
{
    public function __construct(public int $userId, public int $courseId, public int $lessonId)
    {
    }
}
