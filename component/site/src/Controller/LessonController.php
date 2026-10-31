<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

final class LessonController extends EnrollmentController
{
    public function complete(): void
    {
        $this->mutate(true);
    }
}
