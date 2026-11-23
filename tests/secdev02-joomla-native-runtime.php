<?php

/**
 * SECDEV-02: verify Joomla core API imports against the installed target patch.
 *
 * This runs against a real CI Joomla installation with compat6 disabled.
 * Scope: the Joomla symbols imported by the DI provider and component class,
 * plus the core entrypoint methods needed by those integrations.
 * This is a bounded runtime contract check, not a proof of all possible APIs.
 */

declare(strict_types=1);

require_once __DIR__ . '/fixtures/secdev02-native-api-functions.php';

if (in_array('--self-test', $argv, true)) {
    $testSource = <<<'PHP'
<?php
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
// use Joomla\CMS\AbsentClass;
echo 'use Joomla\CMS\FakeClass;';
PHP;
    $expectedImports = ['Joomla\CMS\Factory', 'Joomla\Database\DatabaseInterface'];

    if (secdev02NativeImports($testSource) !== $expectedImports) {
        throw new RuntimeException('Native import parser produced incorrect results');
    }

    $resolver = 'secdev02FixtureClassExists';
    $method = 'secdev02FixtureMethodExists';

    if (secdev02MissingApis(['Joomla\CMS\Factory'], [['Joomla\CMS\Factory', 'getContainer']], $resolver, $method)) {
        throw new RuntimeException('Valid Joomla symbols were rejected');
    }

    $missing = secdev02MissingApis(
        ['Joomla\CMS\RemovedClass'],
        [['Joomla\CMS\Factory', 'removedMethod']],
        $resolver,
        $method
    );

    if (count($missing) !== 2) {
        throw new RuntimeException('Removed Joomla symbols were not detected');
    }

    echo "SECDEV-02 native Joomla API negative fixtures: OK\n";
    exit(0);
}

$root = realpath($argv[1] ?? '');
$repo = realpath(__DIR__ . '/..');

$validRuntime = $root !== false && $repo !== false
    && getenv('JLMS_TEST_DISPOSABLE') === '1'
    && is_file($root . '/administrator/includes/framework.php');

if (!$validRuntime) {
    fwrite(STDERR, "SECDEV-02 requires an installed disposable Joomla runtime\n");
    exit(2);
}

// The paired database-level compat6 check is performed immediately before
// this test in ci.yml; never enable a compatibility layer here.
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

$version = (new \Joomla\CMS\Version())->getShortVersion();
if ($version !== '6.2.0') {
    fwrite(STDERR, "SECDEV-02 Joomla patch mismatch: expected 6.2.0, installed {$version}\n");
    exit(1);
}

$files = [
    'component/administrator/services/provider.php',
    'component/administrator/src/Extension/JlmsComponent.php',
];
$imports = [];

foreach ($files as $file) {
    $source = file_get_contents($repo . '/' . $file);

    if ($source === false) {
        throw new RuntimeException('Native contract source file missing: ' . $file);
    }

    $imports = array_merge($imports, secdev02NativeImports($source));
}

$imports = array_values(array_unique($imports));
if (count($imports) < 10) {
    fwrite(STDERR, "SECDEV-02 native import inventory unexpectedly incomplete\n");
    exit(1);
}

$apiMethods = [
    ['Joomla\CMS\Factory', 'getApplication'],
    ['Joomla\CMS\Factory', 'getContainer'],
    ['Joomla\CMS\Component\ComponentHelper', 'getParams'],
    ['Joomla\CMS\Component\ComponentHelper', 'filterText'],
    ['Joomla\CMS\Plugin\PluginHelper', 'importPlugin'],
];

$exists = 'secdev02RealClassExists';

$errors = secdev02MissingApis(
    $imports,
    $apiMethods,
    $exists,
    'secdev02RealMethodExists'
);

foreach ($errors as $error) {
    fwrite(STDERR, "SECDEV-02: {$error}\n");
}

if ($errors) {
    exit(1);
}

echo 'SECDEV-02 Joomla ' . $version . ' native API contract: '
    . count($imports) . " imports + " . count($apiMethods)
    . " methods verified (compat6 disabled)\n";
