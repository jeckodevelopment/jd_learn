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

function timeExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function timeReject(callable $work, int $code, string $message): void
{
    try {
        $work();
    } catch (\RuntimeException $error) {
        timeExpect($error->getCode() === $code, $message . ": " . $error->getMessage() . " / " . $error->getCode());
        return;
    }
    throw new \RuntimeException($message);
}

function timeKey(): string
{
    $s = bin2hex(random_bytes(16));
    return substr($s, 0, 8) .
        "-" .
        substr($s, 8, 4) .
        "-4" .
        substr($s, 13, 3) .
        "-a" .
        substr($s, 17, 3) .
        "-" .
        substr($s, 20);
}
