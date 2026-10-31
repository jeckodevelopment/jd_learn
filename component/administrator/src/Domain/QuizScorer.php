<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Domain;

final class QuizScorer
{
    /** Exact-set scoring. Correct option IDs are server-only and never serialised as a read DTO. */
    public static function score(array $selected, array $correct, int $points): int
    {
        if ($points < 1 || count($correct) < 1 || count($selected) !== count(array_unique($selected, SORT_STRING))) {
            throw new \InvalidArgumentException('INVALID_ANSWER');
        }
        foreach (array_merge($selected, $correct) as $value) {
            if (!is_string($value) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $value)) {
                throw new \InvalidArgumentException('INVALID_OPTION_ID');
            }
        }
        sort($selected, SORT_STRING);
        sort($correct, SORT_STRING);
        return $selected === $correct ? $points : 0;
    }

    public static function passes(int $score, int $maximum, int $threshold = 7000): bool
    {
        if ($maximum < 1 || $score < 0 || $score > $maximum || $threshold < 1 || $threshold > 10000) {
            throw new \InvalidArgumentException('INVALID_SCORE');
        }
        return $score * 10000 >= $maximum * $threshold;
    }
}
