<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/sec-p2-php-security-floor.functions.php';


/**
 * SEC-P2: runner security-patch floor for the 19 November 2026 PHP advisories.
 *
 * The actual PHP patch, not just its supported minor branch, is required.
 * This gate covers CI only; it does not inspect an administrator's live host.
 *
 * Source: https://www.php.net/archive/2026.php
 */


if (basename(__FILE__) === basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''))) {
    foreach (
        [
        '8.3.36' => false,
        '8.3.37' => true,
        '8.4.27' => false,
        '8.4.28' => true,
        '8.5.12' => false,
        '8.5.13' => true,
        '8.2.36' => false,
        ] as $version => $expected
    ) {
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
