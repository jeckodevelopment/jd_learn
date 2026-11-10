<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class PlaybackContext
{
    /** Constructed only after current access validation. Grant is opaque and bound to scope. */
    public function __construct(
        public int $userId,
        public int $enrollmentId,
        public int $lessonId,
        public string $locale,
        public int $resumeMs,
        public string $authorisationGrant,
        public \DateTimeImmutable $now,
        public ?\DateTimeImmutable $accessExpiresAt = null,
        public bool $preview = false,
        public int $courseId = 0,
        public int $versionId = 0,
    ) {
    }
}
