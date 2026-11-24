<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;

function migrationExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function migrationRow(string $table, int $id): object
{
    global $db;
    return $db->setQuery("SELECT * FROM #__jlms_" . $table . " WHERE id=" . $id)->loadObject();
}

function migrationDenied(callable $fn, int $code, string $message): void
{
    try {
        $fn();
    } catch (RuntimeException $e) {
        migrationExpect($e->getCode() === $code, $message . ": " . $e->getMessage());
        return;
    }
    throw new RuntimeException($message);
}
