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

function learningExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function learningDenied(callable $call, int $code, string $message): void
{
    try {
        $call();
    } catch (\RuntimeException $error) {
        learningExpect(
            $error->getCode() === $code,
            $message . "; received " . $error->getMessage() . " / " . $error->getCode(),
        );
        return;
    }
    throw new \RuntimeException($message);
}

function fixtureUpdate(string $table, object $record, bool $nulls = false): void
{
    global $db;
    $db->updateObject("#__" . $table, $record, "id", $nulls);
}

function fixtureStudent(string $username): \Joomla\CMS\User\User
{
    global $learningGroup;
    $user = new \Joomla\CMS\User\User();
    $data = [
        "name" => "CI Learner",
        "username" => $username,
        "email" => $username . "@example.invalid",
        "password" => "Ci-only-Learner-Password-2026",
        "password2" => "Ci-only-Learner-Password-2026",
        "groups" => [2, $learningGroup],
        "block" => 0,
        "activation" => "",
    ];
    if (!$user->bind($data) || !$user->save()) {
        throw new \RuntimeException("Learner fixture failed: " . $user->getError());
    }
    return new \Joomla\CMS\User\User((int) $user->id);
}

function learningWorkers(array $lessonIds): void
{
    global $student, $enrollment, $root;
    $workers = [];
    foreach ($lessonIds as $lessonId) {
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                __FILE__,
                $root,
                "worker",
                (string) $student->id,
                (string) $enrollment,
                (string) $lessonId,
                bin2hex(random_bytes(16)),
            ],
            [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"],
            ],
            $pipes,
        );
        if (!is_resource($process)) {
            throw new \RuntimeException("Worker could not start");
        }
        if (trim((string) fgets($pipes[1])) !== "ready") {
            throw new \RuntimeException("Worker barrier failed");
        }
        $workers[] = [$process, $pipes];
    }
    foreach ($workers as [$process, $pipes]) {
        fwrite($pipes[0], "go\n");
        fclose($pipes[0]);
    }
    foreach ($workers as [$process, $pipes]) {
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            throw new \RuntimeException("Concurrent completion failed: " . $out . " " . $err);
        }
    }
}
