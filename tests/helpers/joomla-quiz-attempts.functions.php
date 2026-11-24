<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Application\QuizAttemptService as JdQuizAttemptService;
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

function attemptExpect(bool $ok, string $message): void
{
    global$checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function attemptReject(callable $call, int $code, string $message): void
{
    try {
        $call();
    } catch (\RuntimeException $error) {
        attemptExpect($error->getCode() === $code, $message . ': ' . $error->getMessage() . ' / ' . $error->getCode());
        return;
    }throw new \RuntimeException($message);
}

function attemptKey(): string
{
    $s = bin2hex(random_bytes(16));
    return substr(
        $s,
        0,
        8
    ) . '-' . substr(
        $s,
        8,
        4
    ) . '-4' . substr(
        $s,
        13,
        3
    ) . '-a' . substr(
        $s,
        17,
        3
    ) . '-' . substr(
        $s,
        20
    );
}

function attemptAnswers(array $dto, bool $correct = true): string
{
    $a = [];
    foreach ($dto['questions'] as $q) {
        $a[$q['id']] = $correct ? ($q['kind'] === 'multiple' ? ['first','second'] : ['first']) : [];
    }
    return json_encode($a, JSON_THROW_ON_ERROR);
}
