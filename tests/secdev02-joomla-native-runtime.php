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

function secdev02NativeImports(string $source): array
{
    $classes = [];
    foreach (token_get_all($source) as $token) {
        if (!is_array($token) || $token[0] !== T_STRING || $token[1] !== 'use') {
            continue;
        }
        // This function is intentionally not a PHP source parser: use only
        // top-level import declarations on independently validated files.
    }

    // Anchored to complete import declarations, so ordinary prose, PHP
    // expression strings and comments are never matched.
    preg_match_all(
        '/^use[ \t]+(Joomla\\\\(?:CMS|Component|Database|DI|Registry|Session)\\\\[A-Za-z_][A-Za-z0-9_\\\\]*)[ \t]*;/m',
        $source,
        $matches
    );

    return array_values(array_unique($matches[1]));
}

function secdev02MissingApis(
    array $names,
    array $methods,
    callable $hasClass,
    callable $hasMethod
): array {
    $errors = [];

    foreach ($names as $name) {
        if (!$hasClass($name)) {
            $errors[] = 'missing Joomla class/interface: ' . $name;
        }
    }

    foreach ($methods as $target) {
        [$class, $method] = $target;

        if (!$hasClass($class) || !$hasMethod($class, $method)) {
            $errors[] = 'missing Joomla method: ' . $class . '::' . $method;
        }
    }

    return $errors;
}

if (in_array('--self-test', $argv, true)) {
    $testSource = <<<'PHP'
<?php
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
// use Joomla\CMS\AbsentClass;
echo 'use Joomla\CMS\FakeClass;';
PHP;
    if (secdev02NativeImports($testSource) !== [
        'Joomla\CMS\Factory',
        'Joomla\Database\DatabaseInterface',
    ]) {
        throw new RuntimeException('Native import parser produced incorrect results');
    }

    $resolver = static fn (string $name): bool => $name === 'Joomla\CMS\Factory';
    $method = static fn (string $class, string $name): bool =>
        $class === 'Joomla\CMS\Factory' && $name === 'getContainer';

    if (secdev02MissingApis(['Joomla\CMS\Factory'], [['Joomla\CMS\Factory', 'getContainer']], $resolver, $method)) {
        throw new RuntimeException('Valid Joomla symbols were rejected');
    }

    if (count(secdev02MissingApis(
        ['Joomla\CMS\RemovedClass'],
        [['Joomla\CMS\Factory', 'removedMethod']],
        $resolver,
        $method
    )) !== 2) {
        throw new RuntimeException('Removed Joomla symbols were not detected');
    }

    echo "SECDEV-02 native Joomla API negative fixtures: OK\n";
    exit(0);
}

$root = realpath($argv[1] ?? '');
$repo = realpath(__DIR__ . '/..');

if ($root === false || $repo === false || getenv('JLMS_TEST_DISPOSABLE') !== '1'
    || !is_file($root . '/administrator/includes/framework.php')) {
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

define('_JEXEC', 1);
define('JPATH_BASE', $root . '/administrator');
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

$exists = static fn (string $name): bool =>
    class_exists($name) || interface_exists($name) || trait_exists($name) || enum_exists($name);

$errors = secdev02MissingApis(
    $imports,
    $apiMethods,
    $exists,
    static fn (string $class, string $method): bool => method_exists($class, $method)
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
