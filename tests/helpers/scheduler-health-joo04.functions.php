<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Infrastructure\SchedulerHealth;

function checkState(?object $task, bool $enabled, bool $dup, DateTimeImmutable $now, string $expected): void
{
    $got = SchedulerHealth::assess($task, $enabled, $dup, $now)['state'];
    if ($got !== $expected) {
        throw new RuntimeException("Scheduler state: expected $expected, got $got");
    }
}
