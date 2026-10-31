<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Domain;

final class DecimalId
{
    public static function parse(string $value): int
    {
        $max = (string) PHP_INT_MAX;
        if (!preg_match('/^[1-9][0-9]*$/D', $value)
            || strlen($value) > strlen($max)
            || (strlen($value) === strlen($max) && strcmp($value, $max) > 0)) {
            throw new \InvalidArgumentException('INVALID_ID');
        }
        return (int) $value;
    }
}
