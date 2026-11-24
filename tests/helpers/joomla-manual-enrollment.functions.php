<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Application\CourseOperationsService as JdCourseOperationsService;
use Jlms\Component\Jlms\Administrator\Application\ManualEnrollmentService as JdManualEnrollmentService;
use Joomla\CMS\Language\LanguageFactoryInterface as NativeLanguageFactoryInterface;
use Joomla\CMS\User\User as NativeUser;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

function manualExpect(bool $ok, string $message): void
{
    global$checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function manualDenied(callable $fn, int $code, string $message): void
{
    try {
        $fn();
    } catch (\RuntimeException $e) {
        manualExpect($e->getCode() === $code, $message . ': ' . $e->getMessage());
        return;
    }throw new \RuntimeException($message);
}

function manualKey(): string
{
    $k = bin2hex(random_bytes(16));
    return substr($k, 0, 8) . '-' . substr($k, 8, 4) . '-4' . substr($k, 13, 3) . '-a' . substr($k, 17, 3)
        . '-' . substr($k, 20);
}

function manualCommand(array $i): array
{
    $p = $i['impact'];
    return [$p['course_id'],$p['user_id'],$p['language'],'PRIVATE_MANUAL_REASON',manualKey(),$p['hash'],true];
}
