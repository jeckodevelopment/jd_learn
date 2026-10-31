<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

final readonly class VideoCapabilities
{
    public function __construct(
        public bool $position,
        public bool $duration,
        public bool $seek,
        public bool $speed,
        public bool $captions,
        public bool $transcript,
        public bool $expiringPlayback,
        public bool $download,
    ) {
    }
}
