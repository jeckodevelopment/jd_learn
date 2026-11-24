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

function preExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function preDenied(callable $fn, int $code, string $message): void
{
    try {
        $fn();
    } catch (\RuntimeException $e) {
        preExpect($e->getCode() === $code, $message . ": " . $e->getMessage());
        return;
    }
    throw new \RuntimeException($message);
}

function preKey(): string
{
    $k = bin2hex(random_bytes(16));
    return substr($k, 0, 8) .
        "-" .
        substr($k, 8, 4) .
        "-4" .
        substr($k, 13, 3) .
        "-a" .
        substr($k, 17, 3) .
        "-" .
        substr($k, 20);
}

function preCommand(array $i): array
{
    $p = $i["impact"];
    return [
        $p["course_id"],
        $p["user_id"],
        $p["language"],
        "PRIVATE_PREASSIGN_REASON",
        preKey(),
        $p["hash"],
        true,
        true,
    ];
}
