<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Domain;

use Jlms\Component\Jlms\Administrator\Application\CourseSchedule;

/** Attachment availability windows are entered in the course's configured time zone and stored as UTC. */
final class MaterialAvailability
{
    public static function normalize(mixed $publishUp, mixed $publishDown, string $timezone): array
    {
        $start = CourseSchedule::toUtc(self::wall($publishUp), $timezone);
        $end = CourseSchedule::toUtc(self::wall($publishDown), $timezone);
        if ($start !== null && $end !== null && $start >= $end) {
            throw new \InvalidArgumentException('COM_JLMS_CALENDAR_RANGE');
        }
        return ['publish_up' => $start, 'publish_down' => $end];
    }

    public static function validStored(mixed $publishUp, mixed $publishDown): bool
    {
        $start = self::storedValue($publishUp);
        $end = self::storedValue($publishDown);
        try {
            $values = self::normalize($start, $end, 'UTC');
            return $values['publish_up'] === $start && $values['publish_down'] === $end;
        } catch (\Throwable) {
            return false;
        }
    }

    /** MySQL DATETIME(6) may return zero microseconds even for second-precision values. */
    private static function storedValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/\A([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\.000000\z/D', $value, $match)) {
            return $match[1];
        }
        return $value;
    }

    public static function editor(?string $value, string $timezone): string
    {
        return $value === null || $value === '' ? '' : str_replace(' ', 'T', CourseSchedule::display($value, $timezone));
    }

    private static function wall(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }
        if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}\z/D', $value)) {
            return str_replace('T', ' ', $value) . ':00';
        }
        if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}\z/D', $value)) {
            return str_replace('T', ' ', $value);
        }
        return $value;
    }
}
