<?php

declare(strict_types=1);

use Jlms\Component\Jlms\Administrator\Domain\Coverage;
use Jlms\Component\Jlms\Administrator\Domain\QuizScorer;
use Jlms\Component\Jlms\Administrator\Domain\AccessPolicy;
use Jlms\Component\Jlms\Administrator\Domain\LessonTranscript;
use Jlms\Component\Jlms\Administrator\Domain\DecimalId;
use Jlms\Component\Jlms\Administrator\Domain\MaterialAvailability;
use Jlms\Component\Jlms\Administrator\Domain\LessonAvailability;
use Jlms\Contracts\Video\SourceInput;
use Jlms\Contracts\Video\SourceNormalizer;
use Jlms\Contracts\Video\VideoProviderRegistry;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Contracts\Video\AbstractFoundationProvider;

function expect(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) {
        throw new \RuntimeException($message);
    }
    $passed++;
}

function rejects(callable $work, string $message): void
{
    try {
        $work();
    } catch (\DomainException | \InvalidArgumentException $expected) {
        expect(true, $message);
        return;
    }
    throw new \RuntimeException($message);
}
