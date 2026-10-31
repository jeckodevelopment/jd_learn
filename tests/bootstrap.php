<?php

declare(strict_types=1);


\define('_JEXEC', 1);
require_once dirname(__DIR__) . '/libraries/jlms/bootstrap.php';
spl_autoload_register(static function (string $class): void {
    $prefix = 'Jlms\\Component\\Jlms\\Administrator\\';
    if (str_starts_with($class, $prefix)) {
        $file = dirname(__DIR__) . '/component/administrator/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
