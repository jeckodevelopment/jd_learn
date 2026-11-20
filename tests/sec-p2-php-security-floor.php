<?php

declare(strict_types=1);

/**
 * SEC-P2: runner security-patch floor for the 19 November 2026 PHP advisories.
 *
 * The actual PHP patch, not just its supported minor branch, is required.
 * This gate covers CI only; it does not inspect an administrator's live host.
 *
 * Source: https://www.php.net/archive/2026.php
 */
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

if (basename(__FILE__) === basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    foreach ([
        '8.3.36' => false,
        '8.3.37' => true,
        '8.4.27' => false,
        '8.4.28' => true,
        '8.5.12' => false,
        '8.5.13' => true,
        '8.2.36' => false,
    ] as $version => $expected) {
        if (jdLearnPhpSecurityFloor($version) !== $expected) {
            throw new RuntimeException('SEC-P2 PHP patch floor regression.');
        }
    }

    if (!jdLearnPhpSecurityFloor(PHP_VERSION)) {
        fwrite(STDERR, 'SEC-P2 PHP security patch floor not met; update the CI runtime image.\n');
        exit(2);
    }

    echo 'SEC-P2 PHP patch floor verified for running branch ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . "\n";
}
