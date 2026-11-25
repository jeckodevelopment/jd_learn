<?php

declare(strict_types=1);

namespace Jlms\Contracts\Bootstrap;

/**
 * Lazy contract loader used by the intentionally executable Joomla bootstrap.
 * Keeping this callable isolated avoids mixing declarations and side effects.
 */
function autoload(string $class): void
{
    $prefix = 'Jlms\\Contracts\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    if (!preg_match('/^[A-Za-z0-9_\\\\]+$/D', $relative)) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
}
