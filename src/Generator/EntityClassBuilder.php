<?php declare(strict_types=1);

namespace Composite\Sync\Generator;

use Composite\Sync\Helpers\Template;
use Composite\Sync\Providers\AbstractSQLColumn;
use Composite\Sync\Providers\AbstractSQLTable;
use Composite\Sync\Providers\EntityColumnType;
use Composite\Sync\Providers\MySQL\MySQLColumn;
use Composite\Sync\Providers\MySQL\MySQLColumnType;
use Composite\Entity\AbstractEntity;
use Composite\DB\Helpers\DateTimeHelper;

class EntityClassBuilder
{
    /** @var string[] */
    private array $useNamespaces = [
        AbstractEntity::class,
    ];
    /** @var string[] */
    private array $useTableAttributes = [
        'Table',
    ];
    /** @var string[] */
    private array $useSyncAttributes = [];

    public function __construct(
        private readonly AbstractSQLTable $sqlTable,
        private readonly string $connectionName,
        private readonly string $entityClass,
        private readonly array $enums,
    ) {}

    /**
     * @throws \Exception
     */
    public function getFileContent(): string
    {
        return Template::render('templates/entity', $this->getVars());
    }

    /**
     * @return array<string, mixed>
     * @throws \Exception
     */
    private function getVars(): array
    {
        $traits = $properties = [];
        $constructorParams = $this->getEntityProperties();
        if ($this->sqlTable->getColumnByName('deleted_at')) {
            $traits[] = 'Traits\SoftDelete';
            $this->useNamespaces[] = 'Composite\DB\Traits';
            unset($constructorParams['deleted_at']);
        }
        if ($this->sqlTable->getColumnByName('updated_at')) {
            $traits[] = 'Traits\UpdatedAt';
            $this->useNamespaces[] = 'Composite\DB\Traits';
            unset($constructorParams['updated_at']);
        }
        foreach ($constructorParams as $name => $constructorParam) {
            if ($this->sqlTable->getColumnByName($name)?->isAutoincrement) {
                $properties[$name] = $constructorParam;
                unset($constructorParams[$name]);
            }
        }
        if (!preg_match('/^(.+)\\\(\w+)$/', $this->entityClass, $matches)) {
            throw new \Exception("Entity class `$this->entityClass` is incorrect");
        }

        return [
            'phpOpener' => '<?php declare(strict_types=1);',
            'connectionName' => $this->connectionName,
            'tableName' => $this->sqlTable->name,
            'pkNames' => "'" . implode("', '", $this->sqlTable->primaryKeys) . "'",
            'indexes' => $this->getIndexes(),
            'traits' => $traits,
            'entityNamespace' => $matches[1],
            'entityClassShortname' => $matches[2],
            'properties' => $properties,
            'constructorParams' => $constructorParams,
            'useNamespaces' => array_unique($this->useNamespaces),
            'useTableAttributes' => array_unique($this->useTableAttributes),
            'useSyncAttributes' => array_unique($this->useSyncAttributes),
        ];
    }

    private function getEntityProperties(): array
    {
        $noDefaultValue = $hasDefaultValue = [];
        foreach ($this->sqlTable->columns as $column) {
            $attributes = [];
            $isPrimaryKey = \in_array($column->name, $this->sqlTable->primaryKeys);
            if ($isPrimaryKey) {
                $this->useTableAttributes[] = 'PrimaryKey';
                $autoIncrement = $column->isAutoincrement ? '(autoIncrement: true)' : '';
                $attributes[] = '#[PrimaryKey' . $autoIncrement . ']';
            }
            if ($columnAttributeProperties = $column->getColumnAttributeProperties()) {
                $this->useSyncAttributes[] = 'Column';
                $attributes[] = '#[Column(' . implode(', ', $columnAttributeProperties) . ')]';
            }
            $propertyParts = [$this->getPropertyVisibility($column)];
            if ($this->isReadOnly($column)) {
                $propertyParts[] = 'readonly';
            }
            $propertyParts[] = $this->getColumnType($column);
            $propertyParts[] = '$' . $column->name;
            if ($column->hasDefaultValue) {
                $defaultValue = $this->getDefaultValue($column);
                $propertyParts[] = '= ' . $defaultValue;
                $hasDefaultValue[$column->name] = [
                    'attributes' => $attributes,
                    'var' => implode(' ', $propertyParts),
                ];
            } else {
                $noDefaultValue[$column->name] = [
                    'attributes' => $attributes,
                    'var' => implode(' ', $propertyParts),
                ];
            }
        }
        return array_merge($noDefaultValue, $hasDefaultValue);
    }

    private function getPropertyVisibility(AbstractSQLColumn $column): string
    {
        return 'public';
    }

    private function isReadOnly(AbstractSQLColumn $column): bool
    {
        if ($column->isAutoincrement) {
            return true;
        }
        $readOnlyColumns = array_merge(
            $this->sqlTable->primaryKeys,
            [
                'created_at',
                'createdAt',
            ]
        );
        return \in_array($column->name, $readOnlyColumns);
    }

    private function getColumnType(AbstractSQLColumn $column): string
    {
        $entityType = $column->getEntityType();
        if ($entityType === EntityColumnType::Enum) {
            if (!$type = $this->getEnumName($column->name)) {
                $type = 'string';
            }
        } else {
            $type = $entityType->value;
        }
        if ($column->isNullable) {
            $type = '?' . $type;
        }
        return $type;
    }

    public function getDefaultValue(AbstractSQLColumn $column): mixed
    {
        $defaultValue = $column->defaultValue;
        if ($defaultValue === null) {
            return 'null';
        }
        $entityColumnType = $column->getEntityType();
        if ($entityColumnType === EntityColumnType::Datetime) {
            $currentTimestamp = stripos($defaultValue, 'current_timestamp') === 0 || $defaultValue === 'now()';
            if ($currentTimestamp) {
                $defaultValue = "new \DateTimeImmutable()";
            } else {
                if ($defaultValue === 'epoch') {
                    $defaultValue = '1970-01-01 00:00:00';
                } elseif ($defaultValue instanceof \DateTimeInterface) {
                    $defaultValue = DateTimeHelper::dateTimeToString($defaultValue);
                }
                $defaultValue = "new \DateTimeImmutable('" . $defaultValue . "')";
            }
        } elseif ($entityColumnType === EntityColumnType::Enum) {
            if ($enumName = $this->getEnumName($column->name)) {
                $valueName = null;
                /** @var class-string<\UnitEnum> $enumClass */
                $enumClass = $this->enums[$column->name];
                if (class_exists($enumClass)) {
                    foreach ($enumClass::cases() as $enumCase) {
                        if ($enumCase->name === $defaultValue) {
                            $valueName = $enumCase->name;
                        }
                    }
                    if ($valueName) {
                        $defaultValue = $enumName . '::' . $valueName;
                    } else {
                        return 'null';
                    }
                } else {
                    $defaultValue = $enumName . '::' . $defaultValue;
                }
            } else {
                $defaultValue = "'$defaultValue'";
            }
        } elseif ($entityColumnType === EntityColumnType::Boolean) {
            if (strcasecmp((string)$defaultValue, 'false') === 0) {
                return 'false';
            }
            if (strcasecmp((string)$defaultValue, 'true') === 0) {
                return 'true';
            }
            return !empty($defaultValue) ? 'true' : 'false';
        } elseif ($entityColumnType === EntityColumnType::Array) {
            if ($defaultValue === '{}' || $defaultValue === '[]') {
                return '[]';
            }
            if ($decoded = json_decode($defaultValue, true)) {
                return var_export($decoded, true);
            }
            return $defaultValue;
        } else {
            if ($entityColumnType !== EntityColumnType::Integer && $entityColumnType !== EntityColumnType::Float) {
                $defaultValue = "'$defaultValue'";
            }
        }
        return $defaultValue;
    }

    private function getEnumName(string $columnName): ?string
    {
        if (empty($this->enums[$columnName])) {
            return null;
        }
        $enumClass = $this->enums[$columnName];
        if (!\in_array($enumClass, $this->useNamespaces)) {
            $this->useNamespaces[] = $enumClass;
        }
        return substr(strrchr($enumClass, "\\"), 1);
    }

    private function getIndexes(): array
    {
        $result = [];
        foreach ($this->sqlTable->indexes as $index) {
            $attributeIndexColumns = [];
            foreach ($index->columns as $columnName) {
                if (isset($index->order[$columnName]) && strtoupper($index->order[$columnName]) === 'DESC') {
                    $attributeIndexColumns[] = "'$columnName' => 'DESC'";
                } else {
                    $attributeIndexColumns[] = "'$columnName'";
                }
            }
            $properties = [
                "columns: [" . implode(", ", $attributeIndexColumns) . "]",
            ];
            if ($index->isUnique) {
                $properties[] = "isUnique: true";
            }
            if ($index->name) {
                $properties[] = "name: '" . $index->name . "'";
            }
            $this->useSyncAttributes[] = 'Index';
            $result[] = '#[Index(' . implode(', ', $properties) . ')]';
        }
        return $result;
    }
}