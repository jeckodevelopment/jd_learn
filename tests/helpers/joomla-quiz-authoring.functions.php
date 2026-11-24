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

function quizExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function quizReject(callable $work, int $code, string $message): void
{
    try {
        $work();
    } catch (\RuntimeException $error) {
        quizExpect($error->getCode() === $code, $message . ": " . $error->getMessage() . " / " . $error->getCode());
        return;
    }
    throw new \RuntimeException($message);
}
