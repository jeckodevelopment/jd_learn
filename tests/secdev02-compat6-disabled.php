<?php

declare(strict_types=1);

/**
 * SECDEV-02: fail if Joomla 6 integration tests accidentally enable compat6.
 *
 * Run after installing Joomla and again after upgrading JD Learn. Runtime
 * integration tests between these checkpoints exercise Joomla APIs with the
 * backwards-compatibility plugin disabled.
 */

function compat6Disabled(array $rows): bool
{
    if (count($rows) !== 1) {
        return false;
    }

    return (int) $rows[0]['enabled'] === 0
        && (string) $rows[0]['element'] === 'compat6'
        && (string) $rows[0]['folder'] === 'behaviour';
}

if (in_array('--self-test', $argv, true)) {
    assert(compat6Disabled([['enabled' => 0, 'element' => 'compat6', 'folder' => 'behaviour']]));
    assert(!compat6Disabled([['enabled' => 1, 'element' => 'compat6', 'folder' => 'behaviour']]));
    assert(!compat6Disabled([]));
    assert(!compat6Disabled([['enabled' => 0, 'element' => 'compat', 'folder' => 'behaviour']]));
    echo "SECDEV-02 Joomla compat6 disabled negative fixtures: OK\n";
    exit(0);
}

$root = realpath($argv[1] ?? '');

if ($root === false || !is_file($root . '/configuration.php')) {
    fwrite(STDERR, "SECDEV-02: Joomla installation/configuration missing\n");
    exit(2);
}

require_once $root . '/configuration.php';

if (!class_exists('JConfig')) {
    fwrite(STDERR, "SECDEV-02: Joomla configuration class missing\n");
    exit(2);
}

$config = new JConfig();
$prefix = (string) $config->dbprefix;

if (!preg_match('/^[A-Za-z0-9_]+$/D', $prefix)) {
    fwrite(STDERR, "SECDEV-02: invalid database table prefix\n");
    exit(2);
}

try {
    $dsn = getenv('JLMS_TEST_DSN');
    $user = getenv('JLMS_TEST_USER');
    $password = getenv('JLMS_TEST_PASSWORD');

    if (!$dsn || $user === false || $password === false) {
        throw new RuntimeException('Disposable CI database configuration missing');
    }

    $database = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $statement = $database->query(
        "SELECT element, folder, enabled FROM `{$prefix}extensions` "
        . "WHERE type = 'plugin' AND folder = 'behaviour' AND element = 'compat6'"
    );

    if (!$statement || !compat6Disabled($statement->fetchAll())) {
        throw new RuntimeException('Joomla 6 behaviour/compat6 must exist and be disabled');
    }
} catch (Throwable $error) {
    // Avoid emitting credentials or full exception messages from the database.
    fwrite(STDERR, "SECDEV-02: Joomla 6 compatibility plugin is enabled or its state cannot be verified\n");
    exit(1);
}

echo "SECDEV-02: Joomla 6 compatibility plugin disabled (verified against installed database)\n";
