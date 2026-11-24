<?php

declare(strict_types=1);

namespace Joomla\CMS {
    final class Factory
    {
        public static object $application;
        public static function getApplication(): object
        {
            return self::$application;
        }
    }
}
namespace {
    use Jlms\Tests\Fixtures\Cat04Breadcrumb\BreadcrumbApplication;

    require __DIR__ . '/fixtures/cat04-breadcrumb-menu.php';
    require __DIR__ . '/fixtures/cat04-breadcrumb-trail.php';
    require __DIR__ . '/fixtures/cat04-breadcrumb-application.php';
    require __DIR__ . '/../component/site/src/Helper/PublicBreadcrumbHelper.php';





    use Jlms\Component\Jlms\Site\Helper\PublicBreadcrumbHelper;
    \Joomla\CMS\Factory::$application = new BreadcrumbApplication();
    $app = \Joomla\CMS\Factory::getApplication();

    // A direct Joomla menu item already supplies the last breadcrumb.
    $app->menu->active = (object) ['query' => ['option' => 'com_jlms', 'view' => 'course', 'id' => 42]];
    PublicBreadcrumbHelper::addDetail('course', 42, 'A course');
    if (count($app->trail->items) !== 0) {
        throw new \RuntimeException('Duplicate direct-menu breadcrumb');
    }

    // Navigating from a course listing adds only the detail item.
    $app->menu->active = (object) ['query' => ['option' => 'com_jlms', 'view' => 'courses']];
    $app->trail->items = [(object) ['name' => 'Courses']];
    PublicBreadcrumbHelper::addDetail('course', 42, 'A course');
    PublicBreadcrumbHelper::addDetail('course', 42, 'A course');
    if (array_map(static fn ($item) => $item->name, $app->trail->items) !== ['Courses', 'A course']) {
        throw new \RuntimeException('Missing or repeated course breadcrumb');
    }

    // Direct pathway listing routes have the same contract.
    $app->trail->items = [(object) ['name' => 'Pathways']];
    $app->menu->active = (object) ['query' => ['option' => 'com_jlms', 'view' => 'pathways']];
    PublicBreadcrumbHelper::addDetail('pathway', 7, 'A pathway');
    if (array_map(static fn ($item) => $item->name, $app->trail->items) !== ['Pathways', 'A pathway']) {
        throw new \RuntimeException('Pathway breadcrumb missing');
    }
    echo "CAT-04 native breadcrumb direct-menu and listing contracts: OK\n";
}
