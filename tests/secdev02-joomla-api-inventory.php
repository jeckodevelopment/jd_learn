<?php

/**
 * SECDEV-02 full distributed-PHP Joomla API inventory on target Joomla 6.2.1.
 *
 * This validates every statically named Joomla class and Class::method()
 * reference visible in PHP tokens. Dynamic instance calls need flow-sensitive
 * type analysis; their count is reported explicitly, never silently certified.
 */

declare(strict_types=1);

require_once __DIR__ . '/fixtures/secdev02-joomla-api-inventory-functions.php';

if (in_array('--self-test', $argv, true)) {
    $source = <<<'PHP'
<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text as NativeText;
use Joomla\CMS\User\User as NativeUser;
use Joomla\CMS\Event\Finder as FinderEvent;
$container = Factory::getContainer();
$value = NativeText::_('TEST');
$thing = new \Joomla\CMS\User\User();
$event = new FinderEvent\AfterSaveEvent();
$thing->get('id');
(new \Joomla\CMS\User\User())->get('id');
(new NativeUser())?->get('id');
PHP;

    $found = secdev02JoomlaTokenInventory($source);

    if (
        !in_array('Joomla\CMS\Factory', $found['classes'], true)
        || !in_array('Joomla\CMS\User\User', $found['classes'], true)
        || !in_array('Joomla\CMS\Language\Text', $found['classes'], true)
        || !in_array('Joomla\CMS\Event\Finder\AfterSaveEvent', $found['classes'], true)
        || in_array('Joomla\CMS\Event\Finder', $found['classes'], true)
        || count($found['static_methods']) !== 2
        || $found['object_call_sites'] !== 3
        || $found['direct_instance_methods'] !== [['Joomla\\CMS\\User\\User', 'get']]
    ) {
        throw new RuntimeException('Joomla static inventory fixture mismatch');
    }

    $failures = secdev02JoomlaAuditFailures(
        [
            'classes' => ['Joomla\CMS\Missing'],
            'static_methods' => [['Joomla\CMS\Factory', 'removed']],
            'unsupported_imports' => [],
        ],
        static fn (string $className): bool => $className === 'Joomla\CMS\Factory',
        static fn (string $className, string $method): bool => false
    );

    $failures = array_merge($failures, secdev02JoomlaAuditFailures(
        [
            'classes' => [],
            'static_methods' => [],
            'direct_instance_methods' => [['Joomla\\CMS\\User\\User', 'missingMethod']],
            'unsupported_imports' => [],
        ],
        static fn (string $className): bool => $className === 'Joomla\\CMS\\User\\User',
        static fn (string $className, string $method): bool => false
    ));

    if (count($failures) !== 3) {
        throw new RuntimeException('Joomla removed API negative fixture failed');
    }

    echo "SECDEV-02 complete static Joomla PHP API inventory fixtures: OK\n";
    exit(0);
}

$root = realpath($argv[1] ?? '');
$repository = realpath(__DIR__ . '/..');

if (
    $root === false || $repository === false || getenv('JLMS_TEST_DISPOSABLE') !== '1'
    || !is_file($root . '/administrator/includes/framework.php')
) {
    throw new RuntimeException('Disposable installed Joomla CI runtime required');
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/administrator/index.php?option=com_jlms';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
$_SERVER['PHP_SELF'] = '/administrator/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/administrator/index.php';

require __DIR__ . '/fixtures/secdev02-joomla-bootstrap-constants.php';
require $root . '/administrator/includes/defines.php';
require $root . '/administrator/includes/framework.php';

// Joomla component classes are registered through its native extension map.
$container = \Joomla\CMS\Factory::getContainer();
$container->alias('session.web', 'session.web.administrator')
    ->alias('session', 'session.web.administrator')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.web.administrator')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.web.administrator');
$app = $container->get(\Joomla\CMS\Application\AdministratorApplication::class);
\Joomla\CMS\Factory::$application = $app;
$app->createExtensionNamespaceMap();

$version = (new \Joomla\CMS\Version())->getShortVersion();

if ($version !== '6.2.1') {
    throw new RuntimeException('Joomla patch target differs from pinned 6.2.1');
}

$paths = [];

foreach (['component', 'libraries', 'plugins', 'packages'] as $folder) {
    $base = $repository . '/' . $folder;

    if (!is_dir($base)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $entry) {
        if (!$entry->isFile() || $entry->getExtension() !== 'php') {
            continue;
        }

        $relative = substr($entry->getPathname(), strlen($repository) + 1);
        if (str_contains($relative, '/vendor/') || str_contains($relative, '/node_modules/')) {
            continue;
        }

        $paths[] = str_replace('\\', '/', $relative);
    }
}

sort($paths);

if (count($paths) < 250) {
    throw new RuntimeException('Distributed source inventory unexpectedly incomplete');
}

$symbols = [];
$methods = [];
$directInstanceMethods = [];
$invalidImports = [];
$objectCallSites = 0;
$dynamicStaticSites = 0;
$covered = 0;

foreach ($paths as $path) {
    $source = file_get_contents($repository . '/' . $path);
    if ($source === false) {
        throw new RuntimeException('Tracked PHP file disappeared: ' . $path);
    }

    $inventory = secdev02JoomlaTokenInventory($source);
    $covered++;

    foreach ($inventory['classes'] as $name) {
        $symbols[$name] = true;
    }

    foreach ($inventory['static_methods'] as [$className, $method]) {
        $methods[$className . '::' . $method] = [$className, $method];
    }

    foreach ($inventory['direct_instance_methods'] as [$className, $method]) {
        $directInstanceMethods[$className . '::' . $method] = [$className, $method];
    }

    foreach ($inventory['unsupported_imports'] as $item) {
        $invalidImports[] = $path . ': ' . $item;
    }

    $objectCallSites += $inventory['object_call_sites'];
    $dynamicStaticSites += $inventory['dynamic_static_sites'];
}

ksort($symbols);
ksort($methods);
ksort($directInstanceMethods);
$results = [
    'classes' => array_keys($symbols),
    'static_methods' => array_values($methods),
    'direct_instance_methods' => array_values($directInstanceMethods),
    'unsupported_imports' => $invalidImports,
];

$exists = static fn (string $name): bool =>
    class_exists($name) || interface_exists($name) || trait_exists($name) || enum_exists($name);

$failures = secdev02JoomlaAuditFailures(
    $results,
    $exists,
    static fn (string $className, string $method): bool => method_exists($className, $method)
);

foreach ($failures as $failure) {
    fwrite(STDERR, 'SECDEV-02 Joomla API: ' . $failure . "\n");
}

$report = [
    'joomla_version' => $version,
    'compat6' => 'disabled (paired DB gate)',
    'php_files' => $covered,
    'unique_joomla_symbols' => count($symbols),
    'unique_static_method_calls' => count($methods),
    'verified_direct_instance_method_calls' => count($directInstanceMethods),
    'object_method_sites_needing_flow_analysis' => $objectCallSites,
    'unresolved_dynamic_static_sites' => $dynamicStaticSites,
    'failures' => count($failures),
];

echo 'SECDEV-02 Joomla distributed API inventory: '
    . json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";

exit($failures ? 1 : 0);
