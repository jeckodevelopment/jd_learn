<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class VideoMetadata
{
    public function __construct(
        public ?string $title,
        public ?int $durationMs,
        public ?string $posterUrl,
        public string $availability,
        public \DateTimeImmutable $fetchedAt,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
