<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Domain;

final class VideoDuration
{
    public const MAX = 28800000;
    public static function valid(mixed $value): bool { return is_int($value) && $value >= 1000 && $value <= self::MAX; }
    /** 2 seconds for short clips, 1% for longer videos, capped at 5 seconds. */
    public static function tolerance(int $reference): int { return max(2000, min(5000, (int)ceil($reference / 100))); }
    public static function compatible(int $reference, int $observed): bool
    { return self::valid($reference) && self::valid($observed) && abs($reference - $observed) <= self::tolerance($reference); }
}
