<?php

declare(strict_types=1);

function jdLearnPhpSecurityFloor(string $version): bool
{
    $majorMinor = implode('.', array_slice(explode('.', $version), 0, 2));
    $floor = [
        '8.3' => '8.3.37',
        '8.4' => '8.4.28',
        '8.5' => '8.5.13',
    ];

    if (!isset($floor[$majorMinor])) {
        return false;
    }

    return version_compare($version, $floor[$majorMinor], '>=');
}
