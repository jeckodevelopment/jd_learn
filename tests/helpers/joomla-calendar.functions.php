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
use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;
use Jlms\Component\Jlms\Administrator\Application\CourseSchedule as Schedule;

function scheduleExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function scheduleDenied(callable $call, int $code, string $message): void
{
    try {
        $call();
    } catch (RuntimeException $e) {
        scheduleExpect($e->getCode() === $code, $message);
        return;
    }throw new RuntimeException($message);
}
