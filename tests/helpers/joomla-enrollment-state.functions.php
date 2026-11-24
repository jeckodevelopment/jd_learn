<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Application\EnrollmentStateService as JdEnrollmentStateService;
use Joomla\CMS\Language\LanguageFactoryInterface as NativeLanguageFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Session\Session;
use Joomla\Session\SessionInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

function stateExpect(bool $ok, string $message): void
{
    global$checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function stateDenied(callable $call, int $code, string $message): void
{
    try {
        $call();
    } catch (\RuntimeException $e) {
        stateExpect($e->getCode() === $code, $message . ': ' . $e->getMessage());
        return;
    }throw new \RuntimeException($message);
}

function stateKey(): string
{
    $k = bin2hex(random_bytes(16));
    return substr($k, 0, 8) . '-' . substr($k, 8, 4) . '-4' . substr($k, 13, 3) . '-a' . substr($k, 17, 3)
        . '-' . substr($k, 20);
}

function stateCommand(array $i, string $reason = 'PRIVATE_STATE_REASON'): array
{
    return [(int)$i['course']->id,$i['enrollment_id'],$i['impact']['enrollment_revision']
        ,$i['impact']['target_status'],$reason,stateKey(),$i['impact']['hash'],true];
}
