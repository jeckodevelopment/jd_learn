<?php

declare(strict_types=1);

function insertRow(\PDO $db, string $table, array $data): int
{
    $columns = implode(',', array_map(static fn (string $name): string => '`' . $name . '`', array_keys($data)));
    $q = $db->prepare('INSERT INTO `ci_jlms_' . $table . '` (' . $columns . ') VALUES (' . implode(
        ',',
        array_fill(0, count($data), '?')
    ) . ')');
    $q->execute(array_values($data));
    return (int) $db->lastInsertId();
}

function constraintRejects(callable $work): void
{
    try {
        $work();
    } catch (\PDOException $expected) {
        if ($expected->getCode() === '23000') {
            return;
        }
        throw $expected;
    }
    throw new \RuntimeException('Expected database integrity violation');
}
