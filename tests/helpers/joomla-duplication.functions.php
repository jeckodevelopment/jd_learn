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
use Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess;

function copyExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    } $checks++;
}

function copyDenied(callable $work, string $message): void
{
    try {
        $work();
    } catch (Throwable $e) {
        copyExpect(true, $message);
        return;
    }
    throw new RuntimeException($message);
}
