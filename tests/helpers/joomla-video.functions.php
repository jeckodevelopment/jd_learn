<?php

declare(strict_types=1);

use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

function videoExpect(bool $value, string $message): void
{
    global $checks;
    if (!$value) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function videoDenied(callable $work, int $code, string $message): void
{
    try {
        $work();
    } catch (\RuntimeException $error) {
        videoExpect($error->getCode() === $code, $message . ": " . $error->getMessage() . " / " . $error->getCode());
        return;
    }
    throw new \RuntimeException($message);
}

function videoFixtureUpdate(string $table, object $record, bool $nulls = false): void
{
    global $db;
    $db->updateObject("#__" . $table, $record, "id", $nulls);
}
