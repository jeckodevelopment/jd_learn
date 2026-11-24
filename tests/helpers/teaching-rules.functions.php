<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Domain\TeachingRules;
use Jlms\Component\Jlms\Administrator\Domain\Coverage;

function rulesExpect(bool $ok, string $message): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function rulesReject(callable $work, string $key): void
{
    try {
        $work();
    } catch (DomainException $error) {
        rulesExpect($error->getMessage() === $key, 'Expected ' . $key);
        return;
    }throw new RuntimeException('Expected rejection');
}
