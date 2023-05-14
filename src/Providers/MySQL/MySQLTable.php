<?php declare(strict_types=1);

namespace Composite\Sync\Providers\MySQL;

use Composite\Sync\Attributes\SkipMigration;
use Composite\Sync\Helpers\Template;
use Composite\Sync\Providers\AbstractSQLTable;
use Composite\Sync\Attributes\Index;
use Composite\DB\TableConfig;
use Composite\Entity\Schema;

class MySQLTable extends AbstractSQLTable
{
    public final const STORAGE_ENGINE_INNODB = 'InnoDB';
    public final const DEFAULT_COLLATION = 'utf8mb4_unicode_ci';

    public readonly ?string $engine;
    public readonly ?string $collation;

    public function __construct(
        string $name,
        array $columns,
        array $primaryKeys,
        array $indexes,
        ?string $engine = null,
        ?string $collation = null,
    )
    {
        $this->engine = $engine;
        $this->collation = $collation;
        parent::__construct($name, $columns, $primaryKeys, $indexes);
    }

    /**
     * @throws \Exception
     */
    public static function fromEntitySchema(Schema $schema): self
    {
        $tableConfig = TableConfig::fromEntitySchema($schema);
        $columns = [];
        foreach ($schema->columns as $entityColumn) {
            if ($entityColumn->getFirstAttributeByClass(SkipMigration::class)) {
                continue;
            }
            $columns[] = MySQLColumn::fromEntityColumn($entityColumn);
        }
        $indexes = [];
        foreach ($schema->attributes as $attribute) {
            if ($attribute instanceof Index) {
                $indexes[] = MySQLIndex::fromEntityAttribute($tableConfig->tableName, $attribute);
            }
        }
        return new MySQLTable(
            name: $tableConfig->tableName,
            columns: $columns,
            primaryKeys: $tableConfig->primaryKeys,
            indexes: $indexes,
        );
    }

    public function getCreateSqlString(): string
    {
        $rows = [];
        foreach ($this->columns as $column) {
            $rows[] = $column->getSqlString();
        }
        if ($this->primaryKeys) {
            $rows[] = (new MySQLIndex(
                type: MySQLIndexType::PRIMARY,
                name: '',
                columns: $this->primaryKeys,
                isUnique: false,
                order: [],
            ))->getCreateTableSqlString();
        }
        foreach ($this->indexes as $index) {
            $rows[] = $index->getCreateTableSqlString();
        }
        $templateVars = [
            'tableName' => $this->name,
            'rows' => $rows,
            'engine' => $this->engine ?? MySQLTable::STORAGE_ENGINE_INNODB,
            'collate' => $this->collation ?? MySQLTable::DEFAULT_COLLATION,
        ];
        return Template::render('templates/create_table_sql', $templateVars);
    }

    public function getDropSqlString(): string
    {
        $templateVars = ['tableName' => $this->name];
        return Template::render('templates/drop_table_sql', $templateVars);
    }

    public function getAlterSqlString(array $newColumns, array $changedColumns, array $deletedColumns, bool $primaryKeyChanged): string
    {
        $columnsArray = [];
        foreach ($newColumns as $columnName) {
            $columnsArray[] = "ADD " . ($this->getColumnByName($columnName)?->getSqlString() ?? throw new \Exception("Column $columnName not found"));
        }
        foreach ($changedColumns as $columnName) {
            $columnsArray[] = "MODIFY " . ($this->getColumnByName($columnName)?->getSqlString() ?? throw new \Exception("Column $columnName not found"));
        }
        foreach ($deletedColumns as $columnName) {
            $columnsArray[] = "DROP COLUMN `$columnName`";
        }
        if ($primaryKeyChanged) {
            $columnsArray[] = 'DROP PRIMARY KEY';
            if ($this->primaryKeys) {
                $columnsArray[] = 'ADD PRIMARY KEY(`' . implode('`,`', $this->primaryKeys) . '`)';
            }
        }
        if (!$columnsArray) {
            return '';
        }
        $templateVars = [
            'tableName' => $this->name,
            'columns' => $columnsArray,
        ];
        return Template::render('templates/alter_table_sql', $templateVars);
    }
}