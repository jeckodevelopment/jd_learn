<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Domain;

/** Half-open intervals in milliseconds. Calling service validates telemetry and authorisation first. */
final class Coverage
{
    public static function merge(array $existing, array $incoming, int $durationMs): array
    {
        if ($durationMs < 1 || $durationMs > 28800000 || count($existing) + count($incoming) > 10000) {
            throw new \InvalidArgumentException('COVERAGE_LIMIT');
        }
        $intervals = array_merge($existing, $incoming);
        foreach ($intervals as $pair) {
            if (!is_array($pair) || count($pair) !== 2 || !isset($pair[0], $pair[1])
                || !is_int($pair[0]) || !is_int($pair[1]) || $pair[0] < 0
                || $pair[0] >= $pair[1] || $pair[1] > $durationMs) {
                throw new \InvalidArgumentException('INVALID_INTERVAL');
            }
        }
        usort($intervals, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($intervals as [$start, $end]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }
        return $merged;
    }

    public static function coveredMs(array $intervals): int
    {
        return array_sum(array_map(static fn (array $pair): int => $pair[1] - $pair[0], $intervals));
    }

    public static function isComplete(int $coveredMs, int $durationMs, int $threshold = 9000): bool
    {
        if ($durationMs < 1 || $coveredMs < 0 || $coveredMs > $durationMs || $threshold < 1 || $threshold > 10000) {
            throw new \InvalidArgumentException('INVALID_COMPLETION_INPUT');
        }
        return $coveredMs * 10000 >= $durationMs * $threshold;
    }
}
