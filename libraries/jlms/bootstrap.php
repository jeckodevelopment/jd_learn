<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

if (!\defined('JLMS_CONTRACTS_AUTOLOAD')) {
    \define('JLMS_CONTRACTS_AUTOLOAD', true);
    require_once __DIR__ . '/src/Bootstrap/ContractsAutoload.php';
    spl_autoload_register('Jlms\\Contracts\\Bootstrap\\autoload');
}
