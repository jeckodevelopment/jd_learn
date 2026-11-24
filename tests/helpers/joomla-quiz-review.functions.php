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

function reviewExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function reviewReject(callable $work, int $code, string $message): void
{
    try {
        $work();
    } catch (\RuntimeException $error) {
        reviewExpect($error->getCode() === $code, $message . ": " . $error->getMessage() . " / " . $error->getCode());
        return;
    }
    throw new \RuntimeException($message);
}

function reviewKey(): string
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

function reviewAnswers(array $a): string
{
    $r = [];
    foreach ($a["questions"] as $q) {
        $r[$q["id"]] = $q["kind"] === "multiple" ? ["first", "second"] : ["first"];
    }
    return json_encode($r, JSON_THROW_ON_ERROR);
}

function reviewUser(string $name, int $group): \Joomla\CMS\User\User
{
    $u = new \Joomla\CMS\User\User();
    $d = [
        "name" => "CI " . $name,
        "username" => $name,
        "email" => $name . "@example.invalid",
        "password" => "Ci-only-Learner-Password-2026",
        "password2" => "Ci-only-Learner-Password-2026",
        "groups" => [2, $group],
        "block" => 0,
        "activation" => "",
    ];
    if (!$u->bind($d) || !$u->save()) {
        throw new \RuntimeException((string) $u->getError());
    }
    return new \Joomla\CMS\User\User((int) $u->id);
}

function reviewCommand(array $item, string $reason = "PRIVATE_CANCEL_REASON", ?string $key = null): array
{
    $p = $item["impact"];
    return [
        $item["course_id"],
        $item["lesson_id"],
        (int) $item["enrollment"]->id,
        (int) $item["selected"]->id,
        $p["enrollment_revision"],
        $p["attempt_revision"],
        $item["refund"],
        $reason,
        $key ?? reviewKey(),
        $p["hash"],
        true,
    ];
}
