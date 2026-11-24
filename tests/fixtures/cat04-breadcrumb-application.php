<?php

declare(strict_types=1);

namespace Jlms\Tests\Fixtures\Cat04Breadcrumb;

final class BreadcrumbApplication
{
    public BreadcrumbMenu $menu;
    public BreadcrumbTrail $trail;

    public function __construct()
    {
        $this->menu = new BreadcrumbMenu();
        $this->trail = new BreadcrumbTrail();
    }

    public function getMenu(): BreadcrumbMenu
    {
        return $this->menu;
    }

    public function getPathway(): BreadcrumbTrail
    {
        return $this->trail;
    }
}
