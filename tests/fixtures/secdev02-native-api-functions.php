<?php

/**
 * Pure SECDEV-02 Joomla native API test helpers.
 *
 * No executable checks run from this file; the runtime test owns them.
 */

declare(strict_types=1);

/**
 * @return list<string>
 */
function secdev02NativeImports(string $source): array
{
    preg_match_all(
        '/^use[ \t]+(Joomla\\\\(?:CMS|Component|Database|DI|Registry|Session)\\\\[A-Za-z_][A-Za-z0-9_\\\\]*)[ \t]*;/m',
        $source,
        $matches
    );

    return array_values(array_unique($matches[1]));
}

/**
 * @param list<string> $names
 * @param list<array{string,string}> $methods
 *
 * @return list<string>
 */
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

/**
 * Pure fixture resolvers for removed-API tests.
 */
function secdev02FixtureClassExists(string $name): bool
{
    return $name === 'Joomla\CMS\Factory';
}

function secdev02FixtureMethodExists(string $class, string $method): bool
{
    return $class === 'Joomla\CMS\Factory' && $method === 'getContainer';
}

function secdev02RealClassExists(string $name): bool
{
    return class_exists($name)
        || interface_exists($name)
        || trait_exists($name)
        || enum_exists($name);
}

function secdev02RealMethodExists(string $class, string $method): bool
{
    return method_exists($class, $method);
}
