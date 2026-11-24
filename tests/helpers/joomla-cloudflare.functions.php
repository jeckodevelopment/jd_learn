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
use Jlms\Plugin\Jlmsvideo\Cloudflarestream\Provider\Provider;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;
use Jlms\Component\Jlms\Administrator\Application\VideoProgressService;

function streamExpect(bool $value, string $message): void
{
    global $checks;
    if (!$value) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function streamDenied(callable $work, string $key): void
{
    try {
        $work();
    } catch (\RuntimeException $e) {
        streamExpect($e->getMessage() === $key, $key . ": " . $e->getMessage());
        return;
    }
    throw new \RuntimeException("Expected " . $key);
}
