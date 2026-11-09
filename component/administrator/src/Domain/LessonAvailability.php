<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

use Jlms\Component\Jlms\Administrator\Application\CourseSchedule;

/** Lesson publication window entered in the course time zone and stored as UTC. */
final class LessonAvailability
{
    public static function normalize(mixed $publishUp, mixed $publishDown, string $timezone): array
    {
        $values = MaterialAvailability::normalize($publishUp, $publishDown, $timezone);

        return ['publish_up' => $values['publish_up'], 'publish_down' => $values['publish_down']];
    }

    public static function editor(?string $value, string $timezone): string
    {
        return MaterialAvailability::editor($value, $timezone);
    }

    public static function validStored(mixed $publishUp, mixed $publishDown): bool
    {
        return MaterialAvailability::validStored($publishUp, $publishDown);
    }

    public static function state(mixed $publishUp, mixed $publishDown, mixed $releaseAfterDays = null, ?string $startedAt = null, ?\DateTimeImmutable $now = null): string
    {
        if (!self::validStored($publishUp, $publishDown) || !self::validReleaseDays($releaseAfterDays)) {
            throw new \InvalidArgumentException('COM_JLMS_LESSON_SCHEDULE_INVALID');
        }

        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $end = self::date($publishDown);
        if ($end && $end <= $now) {
            return 'closed';
        }
        if ((int) $releaseAfterDays > 0 && ($startedAt === null || $startedAt === '')) {
            return 'awaiting_start';
        }
        $start = self::effectiveStart($publishUp, $releaseAfterDays, $startedAt);
        if ($start && $start > $now) {
            return 'upcoming';
        }

        return 'available';
    }

    public static function effectiveStart(mixed $publishUp, mixed $releaseAfterDays, ?string $startedAt): ?\DateTimeImmutable
    {
        $start = self::date($publishUp);
        if ((int) $releaseAfterDays > 0 && $startedAt !== null && $startedAt !== '') {
            $relative = self::date($startedAt)?->modify('+' . (int) $releaseAfterDays . ' days');
            if ($relative && (!$start || $relative > $start)) {
                $start = $relative;
            }
        }
        return $start;
    }

    public static function displayEffectiveStart(mixed $publishUp, mixed $releaseAfterDays, ?string $startedAt, string $timezone): string
    {
        $start = self::effectiveStart($publishUp, $releaseAfterDays, $startedAt);
        return $start ? CourseSchedule::display($start->format('Y-m-d H:i:s'), $timezone) : '';
    }

    public static function validReleaseDays(mixed $days): bool
    {
        return $days === null || $days === '' || (is_int($days) && $days >= 0 && $days <= 3650)
            || (is_string($days) && preg_match('/\A(?:0|[1-9][0-9]{0,3})\z/D', $days) === 1 && (int) $days <= 3650);
    }

    public static function normalizeReleaseDays(mixed $days): ?int
    {
        if ($days === null || $days === '') {
            return null;
        }
        if (!self::validReleaseDays($days)) {
            throw new \InvalidArgumentException('COM_JLMS_LESSON_SCHEDULE_INVALID');
        }
        return (int) $days;
    }

    public static function reachable(mixed $publishUp, mixed $publishDown, array $courseWindow): bool
    {
        if (!self::validStored($publishUp, $publishDown)) {
            return false;
        }

        $lower = array_values(array_filter([$publishUp, $courseWindow['publish_up'] ?? null, $courseWindow['starts_at'] ?? null],
            static fn ($value): bool => $value !== null && $value !== ''));
        $upper = array_values(array_filter([$publishDown, $courseWindow['publish_down'] ?? null, $courseWindow['ends_at'] ?? null],
            static fn ($value): bool => $value !== null && $value !== ''));
        if (!$lower || !$upper) {
            return true;
        }

        $latestStart = max(array_map(static fn (string $value): int => self::date($value)->getTimestamp(), $lower));
        $earliestEnd = min(array_map(static fn (string $value): int => self::date($value)->getTimestamp(), $upper));

        return $latestStart < $earliestEnd;
    }

    public static function display(?string $value, string $timezone): string
    {
        return $value === null || $value === '' ? '' : CourseSchedule::display(self::dateValue($value), $timezone);
    }

    private static function date(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new \DateTimeImmutable(self::dateValue((string) $value), new \DateTimeZone('UTC'));
    }

    private static function dateValue(string $value): string
    {
        if (preg_match('/\A([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\.000000\z/D', $value, $match)) {
            return $match[1];
        }

        return $value;
    }
}
