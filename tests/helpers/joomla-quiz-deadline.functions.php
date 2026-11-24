<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Application\QuizAttemptService as JdQuizAttemptService;
use Jlms\Component\Jlms\Administrator\Application\QuizDeadlineService as JdQuizDeadlineService;
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

function deadlineExpect(bool $ok, string $message): void
{
    global$checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function deadlineReject(callable $work, int $code, string $message): void
{
    try {
        $work();
    } catch (\RuntimeException $error) {
        deadlineExpect($error->getCode() === $code, $message . ': ' . $error->getMessage() . ' / ' . $error->getCode());
        return;
    }throw new \RuntimeException($message);
}

function deadlineKey(): string
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

function deadlineUser(string $name, int $group): NativeUser
{
    $u = new NativeUser();
    $d = ['name' => 'CI ' . $name,'username' => $name,'email' => $name . '@example.invalid','password' =>
         'Ci-only-Learner-Password-2026','password2' => 'Ci-only-Learner-Password-2026','groups' => [2,
        $group],'block' => 0,'activation' => ''];
    if (!$u->bind($d) || !$u->save()) {
        throw new \RuntimeException((string)$u->getError());
    }
    return new NativeUser((int)$u->id);
}

function deadlineCommand(array $i, string $reason = 'PRIVATE_DEADLINE_REASON'): array
{
    return [$i['course_id'],$i['lesson_id'],(int)$i['enrollment']->id,(int)$i['attempt']->id
        ,$i['impact']['enrollment_revision'],$i['impact']['attempt_revision'],$i['minutes'],$reason
        ,deadlineKey(),$i['impact']['hash'],true];
}
