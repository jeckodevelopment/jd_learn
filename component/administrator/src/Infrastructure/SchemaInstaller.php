<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\Database\DatabaseInterface;

/** DDL is not transactional on these engines. Every completed step is checked on a retry. */
final class SchemaInstaller
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function apply(string $schema): void
    {
        $schema = preg_replace('/--[^\n]*/', '', $schema);
        foreach (explode(';', $schema) as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            if (preg_match('/^CREATE TABLE IF NOT EXISTS `([^`]+)`/i', $statement, $match)) {
                $this->db->setQuery($statement)->execute();
                // Do not silently accept an incompatible retained table after uninstall/reinstall.
                $metadata = $this->db->getTableColumns($match[1], false);
                $columns = array_keys($metadata);
                preg_match_all('/^  `([^`]+)` /m', $statement, $declared);
                $expected = $declared[1];
                sort($columns);
                sort($expected);
                if ($columns !== $expected) {
                    throw new \RuntimeException('COM_JLMS_SCHEMA_MISMATCH');
                }
                preg_match_all('/^  `([^`]+)` ([A-Z]+(?:\(\d+\))?(?: UNSIGNED)?) (NOT NULL|NULL)/m', $statement, $types, PREG_SET_ORDER);
                foreach ($types as $column) {
                    $normalise = static fn (string $type): string => strtolower((string) preg_replace('/(tinyint|smallint|mediumint|bigint|int)\(\d+\)/i', '$1', $type));
                    $actual = $metadata[$column[1]];
                    if ($normalise($actual->Type) !== $normalise($column[2])
                        || (($actual->Null === 'NO') !== ($column[3] === 'NOT NULL'))) {
                        throw new \RuntimeException('COM_JLMS_SCHEMA_MISMATCH');
                    }
                }
                $keys = [];
                foreach ($this->db->getTableKeys($match[1]) as $key) {
                    $keys[$key->Key_name][(int) $key->Seq_in_index] = $key->Column_name;
                }
                preg_match_all('/(PRIMARY KEY|UNIQUE KEY `([^`]+)`|KEY `([^`]+)`) \(([^)]+)\)/', $statement, $indexes, PREG_SET_ORDER);
                foreach ($indexes as $index) {
                    $name = $index[1] === 'PRIMARY KEY' ? 'PRIMARY' : ($index[2] ?: $index[3]);
                    preg_match_all('/`([^`]+)`/', $index[4], $fields);
                    $actual = $keys[$name] ?? [];
                    ksort($actual);
                    if (array_values($actual) !== $fields[1]) {
                        throw new \RuntimeException('COM_JLMS_SCHEMA_MISMATCH');
                    }
                }
                continue;
            }
            if (preg_match('/^ALTER TABLE `([^`]+)` ADD CONSTRAINT `([^`]+)`/i', $statement, $match)) {
                $table = $this->db->replacePrefix($match[1]);
                $constraint = $this->db->replacePrefix($match[2]);
                $q = $this->db->createQuery()->select('COUNT(*)')
                    ->from('information_schema.TABLE_CONSTRAINTS')
                    ->where('CONSTRAINT_SCHEMA = DATABASE()')->where('TABLE_NAME = :table')
                    ->where('CONSTRAINT_NAME = :constraint')->bind(':table', $table)->bind(':constraint', $constraint);
                if (!(int) $this->db->setQuery($q)->loadResult()) {
                    $this->db->setQuery($statement)->execute();
                }
                preg_match('/FOREIGN KEY \(([^)]+)\) REFERENCES `([^`]+)` \(([^)]+)\)/', $statement, $reference);
                preg_match_all('/`([^`]+)`/', $reference[1], $childColumns);
                preg_match_all('/`([^`]+)`/', $reference[3], $parentColumns);
                $parentTable = $this->db->replacePrefix($reference[2]);
                $q = $this->db->createQuery()->select('COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME')
                    ->from('information_schema.KEY_COLUMN_USAGE')->where('CONSTRAINT_SCHEMA = DATABASE()')
                    ->where('TABLE_NAME = :table')->where('CONSTRAINT_NAME = :constraint')
                    ->order('ORDINAL_POSITION')->bind(':table', $table)->bind(':constraint', $constraint);
                $actual = $this->db->setQuery($q)->loadAssocList();
                $expected = [];
                foreach ($childColumns[1] as $position => $column) {
                    $expected[] = ['COLUMN_NAME' => $column, 'REFERENCED_TABLE_NAME' => $parentTable,
                        'REFERENCED_COLUMN_NAME' => $parentColumns[1][$position]];
                }
                if ($actual !== $expected) {
                    throw new \RuntimeException('COM_JLMS_SCHEMA_MISMATCH');
                }
                continue;
            }
            throw new \RuntimeException('COM_JLMS_UNSUPPORTED_SCHEMA_STATEMENT');
        }
    }
}
