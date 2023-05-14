<?php declare(strict_types=1);

namespace Composite\Sync\Providers\MySQL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use iamcal\SQLParser;

class MySQLParser
{
    public function __construct(
        private readonly string $tableName,
        private readonly string $sql,
    ) {}

    /**
     * @throws \Exception
     */
    public static function fromConnection(Connection $connection, string $tableName): MySQLParser
    {
        try {
            $showResult = $connection
                ->executeQuery("SHOW CREATE TABLE $tableName")
                ->fetchAssociative();
            $sql = $showResult['Create Table'];
        } catch (TableNotFoundException) {
            $sql = '';
        }
        return new MySQLParser(
            tableName: $tableName,
            sql: $sql,
        );
    }

    public function getSQLTable(): ?MySQLTable
    {
        if (!$this->sql) {
            return null;
        }
        $parser = new SQLParser();
        $data = $parser->parse($this->sql);
        if (empty($data[$this->tableName])) {
            throw new \Exception('SQL parsing failed');
        }
        $tableArray = $data[$this->tableName];
        $props = $tableArray['props'] ?? [];
        $engine = $props['ENGINE'] ?? null;
        $collation = $props['COLLATE'] ?? null;

        $columns = $indexes = $primaryKeys = [];
        if (!empty($tableArray['fields'])) {
            foreach ($tableArray['fields'] as $fieldArray) {
                $column = MySQLColumn::fromSqlParserArray($fieldArray);
                $columns[] = $column;
            }
        }
        if (!empty($tableArray['indexes'])) {
            foreach ($tableArray['indexes'] as $indexArray) {
                $index = MySQLIndex::fromSqlParserArray($indexArray);
                if ($index->type === MySQLIndexType::PRIMARY) {
                    $primaryKeys = $index->columns;
                    continue;
                }
                $indexes[] = $index;
            }
        }
        return new MySQLTable(
            name: $this->tableName,
            columns: $columns,
            primaryKeys: $primaryKeys,
            indexes: $indexes,
            engine: $engine,
            collation: $collation,
        );
    }
}