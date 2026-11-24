<?php

declare(strict_types=1);

namespace Jlms\Tests\Fixtures\Cat04Breadcrumb;

final class BreadcrumbTrail
{
    public array $items = [];

    public function getPathway(): array
    {
        return $this->items;
    }

    public function addItem(string $title): void
    {
        $this->items[] = (object) ['name' => $title];
    }
}
