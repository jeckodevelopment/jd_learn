<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Domain\QuestionDefinition;
use Jlms\Component\Jlms\Administrator\Domain\QuizScorer;

function qExpect(bool $ok, string $message): void
{
    global$checks;
    if (!$ok) {
        throw new \RuntimeException($message);
    }
    $checks++;
}

function qReject(callable $work, string $message): void
{
    try {
        $work();
    } catch (\RuntimeException $e) {
        qExpect(in_array($e->getCode(), [400,422], true), $message);
        return;
    }throw new \RuntimeException($message);
}

function qReason(callable $work, string $reason): void
{
    try {
        $work();
    } catch (\RuntimeException $e) {
        qExpect($e->getMessage() === $reason, 'specific validation reason: ' . $reason);
        return;
    }throw new \RuntimeException('Expected validation reason: ' . $reason);
}
