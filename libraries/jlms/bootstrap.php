<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

if (!\defined('JLMS_CONTRACTS_AUTOLOAD')) {
    // The constant declaration is isolated from this executable entry point.
    require_once __DIR__ . '/src/Bootstrap/ContractsBootstrapFlag.php';
    require_once __DIR__ . '/src/Bootstrap/ContractsAutoload.php';
    spl_autoload_register('Jlms\\Contracts\\Bootstrap\\autoload');
}
