<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

/**
 * Aggregates the progress of a curated pathway without granting course access.
 */
final class PathwayProgress
{
    /**
     * @param array<int, array{percent?: ?int, access?: string}> $courses
     * @return array{percent: ?int, unavailable: bool, required: int}
     */
    public static function summarize(array $courses): array
    {
        if ($courses === []) {
            return ['percent' => null, 'unavailable' => false, 'required' => 0];
        }

        $sum = 0;
        $unavailable = false;

        foreach ($courses as $course) {
            $percent = $course['percent'] ?? 0;

            if (($course['access'] ?? 'unavailable') !== 'available') {
                $unavailable = true;
                $percent = 0;
            }

            $sum += max(0, min(100, (int) ($percent ?? 0)));
        }

        return [
            'percent' => (int) floor($sum / count($courses)),
            'unavailable' => $unavailable,
            'required' => count($courses),
        ];
    }
}
