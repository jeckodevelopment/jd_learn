<?php

declare(strict_types=1);

namespace Jlms\Tests\Fixtures\Cat04Breadcrumb;

final class BreadcrumbMenu
{
    public ?object $active = null;

    public function getActive(): ?object
    {
        return $this->active;
    }
}
