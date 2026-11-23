<?php

/**
 * SECDEV-02: functions-only PHP token inventory for Joomla API references.
 *
 * Static class/interface/trait symbols, imported aliases and Class::method
 * calls are exhaustively enumerated in tracked distributed PHP files.
 * Dynamic ->method calls have no static receiver type and are counted rather
 * than being falsely marked verified.
 */

declare(strict_types=1);

function secdev02JoomlaTokenInventory(string $source): array
{
    $tokens = token_get_all($source);
    $aliases = [];
    $classes = [];
    $methods = [];
    $directInstanceMethods = [];
    $namespaceBases = [];
    $objectCalls = 0;
    $dynamicStaticCalls = 0;
    $unresolvedImports = [];
    $line = 1;

    foreach ($tokens as $i => $token) {
        if (!is_array($token) || $token[0] !== T_USE) {
            continue;
        }

        // Identify ordinary imports (namespace, classes and interfaces);
        // closure captures and trait uses do not start with a Joomla name.
        $j = $i + 1;

        while (
            isset($tokens[$j]) && is_array($tokens[$j])
            && $tokens[$j][0] === T_WHITESPACE
        ) {
            $j++;
        }

        if (!isset($tokens[$j]) || !is_array($tokens[$j])) {
            continue;
        }

        $name = $tokens[$j][1];
        if (
            !str_starts_with($name, 'Joomla\\')
            && !str_starts_with($name, '\\Joomla\\')
        ) {
            continue;
        }

        if (isset($tokens[$j + 1]) && $tokens[$j + 1] === '\\') {
            $unresolvedImports[] = $name;
            continue;
        }

        $alias = substr($name, (int) strrpos($name, '\\') + 1);
        $k = $j + 1;

        while (
            isset($tokens[$k]) && is_array($tokens[$k])
            && $tokens[$k][0] === T_WHITESPACE
        ) {
            $k++;
        }

        if (
            isset($tokens[$k]) && is_array($tokens[$k])
            && $tokens[$k][0] === T_AS
        ) {
            $k++;
            while (
                isset($tokens[$k]) && is_array($tokens[$k])
                && $tokens[$k][0] === T_WHITESPACE
            ) {
                $k++;
            }
            if (isset($tokens[$k]) && is_array($tokens[$k])) {
                $alias = $tokens[$k][1];
            }
        }

        if (
            isset($aliases[strtolower($alias)])
            && $aliases[strtolower($alias)] !== ltrim($name, '\\')
        ) {
            $unresolvedImports[] = $name . ' alias collision';
        }

        $aliases[strtolower($alias)] = ltrim($name, '\\');
    }

    foreach ($tokens as $i => $token) {
        if (!is_array($token)) {
            continue;
        }

        [$kind, $name, $line] = $token;
        if ($kind === T_OBJECT_OPERATOR || $kind === T_NULLSAFE_OBJECT_OPERATOR) {
            $j = $i + 1;

            while (
                isset($tokens[$j]) && is_array($tokens[$j])
                && $tokens[$j][0] === T_WHITESPACE
            ) {
                $j++;
            }
            if (
                isset($tokens[$j]) && is_array($tokens[$j])
                && $tokens[$j][0] === T_STRING
            ) {
                $k = $j + 1;
                while (
                    isset($tokens[$k]) && is_array($tokens[$k])
                    && $tokens[$k][0] === T_WHITESPACE
                ) {
                    $k++;
                }
                if (($tokens[$k] ?? null) === '(') {
                    $objectCalls++;
                }
            }
        }

        if (!in_array($kind, [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_STRING], true)) {
            continue;
        }

        $className = ltrim($name, '\\');

        if ($kind === T_STRING) {
            $className = $aliases[strtolower($name)] ?? '';
        } elseif ($kind === T_NAME_QUALIFIED && !str_starts_with($className, 'Joomla\\')) {
            $parts = explode('\\', $className, 2);
            $base = $aliases[strtolower($parts[0])] ?? null;
            if ($base !== null && isset($parts[1])) {
                $className = $base . '\\' . $parts[1];
                $namespaceBases[$base] = true;
            }
        }

        if (!str_starts_with($className, 'Joomla\\')) {
            continue;
        }

        $classes[$className] = true;

        $j = $i + 1;
        while (
            isset($tokens[$j]) && is_array($tokens[$j])
            && $tokens[$j][0] === T_WHITESPACE
        ) {
            $j++;
        }

        if (
            !isset($tokens[$j]) || !is_array($tokens[$j])
            || $tokens[$j][0] !== T_DOUBLE_COLON
        ) {
            continue;
        }

        $j++;
        while (
            isset($tokens[$j]) && is_array($tokens[$j])
            && $tokens[$j][0] === T_WHITESPACE
        ) {
            $j++;
        }

        if (
            !isset($tokens[$j]) || !is_array($tokens[$j])
            || $tokens[$j][0] !== T_STRING
        ) {
            $dynamicStaticCalls++;
            continue;
        }

        $method = $tokens[$j][1];

        if (strtolower($method) === 'class') {
            continue;
        }

        $k = $j + 1;
        while (
            isset($tokens[$k]) && is_array($tokens[$k])
            && $tokens[$k][0] === T_WHITESPACE
        ) {
            $k++;
        }

        if (($tokens[$k] ?? null) === '(') {
            $methods[$className . '::' . $method] = [$className, $method];
        }
    }


    // Directly instantiated Joomla receivers have a statically known class.
    // This intentionally does not guess the type of ordinary $object variables.
    foreach ($tokens as $i => $token) {
        if (!is_array($token) || $token[0] !== T_NEW) {
            continue;
        }

        $j = $i + 1;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }

        $classToken = $tokens[$j] ?? null;
        if (!is_array($classToken) || !in_array(
            $classToken[0],
            [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_STRING],
            true
        )) {
            continue;
        }

        $className = ltrim($classToken[1], '\\');
        if ($classToken[0] === T_STRING) {
            $className = $aliases[strtolower($className)] ?? '';
        } elseif ($classToken[0] === T_NAME_QUALIFIED && !str_starts_with($className, 'Joomla\\')) {
            $parts = explode('\\', $className, 2);
            $className = isset($aliases[strtolower($parts[0])], $parts[1])
                ? $aliases[strtolower($parts[0])] . '\\' . $parts[1] : '';
        }

        if (!str_starts_with($className, 'Joomla\\')) {
            continue;
        }

        $j++;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }

        // Only a syntactically complete constructor call gives a safe receiver.
        if (($tokens[$j] ?? null) !== '(') {
            continue;
        }
        $depth = 0;
        for (; isset($tokens[$j]); $j++) {
            if ($tokens[$j] === '(') {
                $depth++;
            } elseif ($tokens[$j] === ')' && --$depth === 0) {
                $j++;
                break;
            }
        }
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        // Handle parenthesized expressions: (new NativeClass())->method().
        if (($tokens[$j] ?? null) === ')') {
            $j++;
            while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                $j++;
            }
        }
        if (!is_array($tokens[$j] ?? null) || !in_array(
            $tokens[$j][0],
            [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR],
            true
        )) {
            continue;
        }
        $j++;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        $methodToken = $tokens[$j] ?? null;
        if (!is_array($methodToken) || $methodToken[0] !== T_STRING) {
            continue;
        }
        $method = $methodToken[1];
        $j++;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if (($tokens[$j] ?? null) === '(') {
            $directInstanceMethods[$className . '::' . $method] = [$className, $method];
        }
    }

    // An import used as a namespace prefix is not itself a class.
    foreach (array_keys($namespaceBases) as $base) {
        unset($classes[$base]);
    }

    return [
        'classes' => array_keys($classes),
        'static_methods' => array_values($methods),
        'direct_instance_methods' => array_values($directInstanceMethods),
        'object_call_sites' => $objectCalls,
        'dynamic_static_sites' => $dynamicStaticCalls,
        'unsupported_imports' => $unresolvedImports,
    ];
}

function secdev02JoomlaAuditFailures(
    array $inventory,
    callable $classExists,
    callable $methodExists
): array {
    $errors = [];

    foreach ($inventory['classes'] as $className) {
        if (!$classExists($className)) {
            $errors[] = 'missing class: ' . $className;
        }
    }

    foreach ($inventory['static_methods'] as [$className, $method]) {
        if (!$classExists($className) || !$methodExists($className, $method)) {
            $errors[] = 'missing static API: ' . $className . '::' . $method;
        }
    }

    foreach ($inventory['direct_instance_methods'] ?? [] as [$className, $method]) {
        if (!$classExists($className) || !$methodExists($className, $method)) {
            $errors[] = 'missing direct instance API: ' . $className . '->' . $method;
        }
    }

    foreach ($inventory['unsupported_imports'] as $import) {
        $errors[] = 'unsupported Joomla grouped/ambiguous import: ' . $import;
    }

    return $errors;
}
