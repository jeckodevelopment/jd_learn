<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class PlaybackDescriptor
{
    /** @param array<string, scalar|null> $playerOptions Whitelisted client-safe values only. */
    public function __construct(
        public string $provider,
        public int $sourceRevision,
        public string $playerAsset,
        public array $playerOptions,
        public VideoCapabilities $capabilities,
        public ?\DateTimeImmutable $expiresAt = null,
        public ?string $playbackToken = null,
    ) {
    }
}
