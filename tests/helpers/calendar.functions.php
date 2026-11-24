<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Application\CourseSchedule as Schedule;

function calendarCheck(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    } $checks++;
}

function calendarReject(callable $call, string $message): void
{
    try {
        $call();
    } catch (InvalidArgumentException) {
        calendarCheck(true, $message);
        return;
    } throw new RuntimeException($message);
}
