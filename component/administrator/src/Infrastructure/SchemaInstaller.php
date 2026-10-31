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
                $columns = array_keys($this->db->getTableColumns($match[1]));
                preg_match_all('/^  `([^`]+)` /m', $statement, $declared);
                $expected = $declared[1];
                sort($columns);
                sort($expected);
                if ($columns !== $expected) {
                    throw new \RuntimeException('COM_JLMS_SCHEMA_MISMATCH');
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
                continue;
            }
            throw new \RuntimeException('COM_JLMS_UNSUPPORTED_SCHEMA_STATEMENT');
        }
    }
}
