<?php declare(strict_types=1);

namespace Composite\Sync\Providers\MySQL;

use Composite\Sync\Attributes\Column;
use Composite\Sync\Providers\AbstractSQLColumn;
use Composite\Sync\Providers\EntityColumnType;
use Composite\Entity\Columns\AbstractColumn;
use Composite\DB\Attributes\PrimaryKey;
use Composite\Entity\Columns;
use Composite\Entity\Helpers\DateTimeHelper;

/**
 * @property MySQLColumnType $type
 */
class MySQLColumn extends AbstractSQLColumn
{
    public readonly bool $unsigned;
    public readonly int $fsp;
    public readonly ?string $collation;
    public readonly ?string $onUpdate;

    public function __construct(
        string $name,
        MySQLColumnType $type,
        ?int $size = null,
        ?int $precision = null,
        ?int $scale = null,
        bool $isNullable = false,
        bool $hasDefaultValue = false,
        mixed $defaultValue = null,
        bool $isAutoincrement = false,
        ?array $values = null,
        bool $unsigned = false,
        int $fsp = 0,
        ?string $collation = null,
        ?string $onUpdate = null,
    ) {
        $this->unsigned = $unsigned;
        $this->fsp = $fsp;
        $this->collation = $collation;
        $this->onUpdate = $onUpdate;
        parent::__construct(
            name: $name,
            type: $type,
            size: $size,
            precision: $precision,
            scale: $scale,
            isNullable: $isNullable,
            hasDefaultValue: $hasDefaultValue,
            defaultValue: $defaultValue,
            isAutoincrement: $isAutoincrement,
            values: $values,
        );
    }

    /**
     * @throws \Exception
     */
    public static function fromEntityColumn(AbstractColumn $entityColumn): self
    {
        $columnAttribute = $entityColumn->getFirstAttributeByClass(Column::class);
        $primaryKeyAttribute = $entityColumn->getFirstAttributeByClass(PrimaryKey::class);
        $isAutoIncrement = $primaryKeyAttribute?->autoIncrement ?? false;
        $type = $columnAttribute?->type ?
            MySQLColumnType::fromString($columnAttribute->type) :
            MySQLColumnType::fromEntityColumn($entityColumn);

        $fsp = $type === MySQLColumnType::TIMESTAMP && $columnAttribute?->size !== 4 ? 6 : 0;

        $isNullable = self::getIsNullable($entityColumn);
        $hasDefaultValue = $entityColumn->hasDefaultValue;
        $defaultValue = self::getDefaultValue($entityColumn, $columnAttribute);

        return new MySQLColumn(
            name: $entityColumn->name,
            type: $type,
            size: self::getSize($entityColumn, $columnAttribute),
            precision: $columnAttribute?->precision,
            scale: $columnAttribute?->scale,
            isNullable: $isNullable,
            hasDefaultValue: $hasDefaultValue,
            defaultValue: $defaultValue,
            isAutoincrement: $isAutoIncrement,
            values: self::getValues($entityColumn),
            fsp: $fsp,
            collation: $columnAttribute?->collation,
        );
    }

    public function getEntityType(): EntityColumnType
    {
        if ($this->type === MySQLColumnType::TINYINT && $this->size === 1) {
            return EntityColumnType::Boolean;
        }
        if ($this->type->isString()) {
            return EntityColumnType::String;
        }
        if ($this->type->isInteger()) {
            return EntityColumnType::Integer;
        }
        if ($this->type->isFloat()) {
            return EntityColumnType::Float;
        }
        if ($this->type->isDateTime()) {
            return EntityColumnType::Datetime;
        }
        if ($this->type->isEnum()) {
            return EntityColumnType::Enum;
        }
        return match($this->type) {
            MySQLColumnType::JSON => EntityColumnType::Array,
            default => EntityColumnType::String,
        };
    }

    /**
     * @throws \Exception
     */
    public static function fromSqlParserArray(array $data): MySQLColumn
    {
        if (!$columnName = $data['name'] ?? null) {
            throw new \Exception(sprintf('Missed field `name` in column array'));
        }
        if (!$typeRaw = $data['type'] ?? null) {
            throw new \Exception(sprintf('Missed field `type` in column array'));
        }
        $type = MySQLColumnType::fromString($typeRaw);
        $more = $data['more'] ?? [];
        $notNull = (isset($data['null']) && $data['null'] === false) || in_array('NOT NULL', $more);
        $size = !empty($data['length']) ? (int)$data['length'] : null;
        $fsp = !empty($data['fsp']) ? (int)$data['fsp'] : 0;
        $collation = $data['collation'] ?? null;
        $values = $data['values'] ?? null;
        $onUpdate = null;

        if ($more) {
            $firstMoreElement = $more[0];
            if (!$size && $firstMoreElement === '(' && !empty($more[1]) && ($more[2] ?? null) === ')') {
                $size = (int)$more[1];
                array_splice($more, 0, 3);
                $firstMoreElement = $more[0] ?? null;
            }
            if ($firstMoreElement && strtolower($firstMoreElement) === 'on update' && !empty($more[1])) {
                $onUpdate = $more[1];
                array_splice($more, 0, 2);
                $firstMoreElement = $more[0] ?? null;
            }
            if ($firstMoreElement === '(' && !empty($more[1]) && ($more[2] ?? null) === ')') {
                $onUpdate .= $more[0] . $more[1] . $more[2];
                array_splice($more, 0, 3);
                $firstMoreElement = $more[0] ?? null;
            }
        }

        if ($type->isFloat()) {
            $precision = $size;
            $scale = !empty($data['decimals']) ? (int)$data['decimals'] : null;
            $size = null;
        } else {
            $precision = $scale = null;
        }

        if (array_key_exists('default', $data)) {
            $hasDefaultValue = true;
            $default = $data['default'];
            if ($default === 'NULL') {
                $default = null;
            } elseif ($default !== null) {
                if ($type->isInteger()) {
                    $default = intval($default);
                } elseif ($type->isFloat()) {
                    $default = floatval($default);
                }
            }
        } else {
            $hasDefaultValue = false;
            $default = null;
        }
        $isAutoIncrement = !empty($data['auto_increment']);
        $isUnsigned = !empty($data['unsigned']);

        return new MySQLColumn(
            name: $columnName,
            type: $type,
            size: $size,
            precision: $precision,
            scale: $scale,
            isNullable: !$notNull,
            hasDefaultValue: $hasDefaultValue,
            defaultValue: $default,
            isAutoincrement: $isAutoIncrement,
            values: $values,
            unsigned: $isUnsigned,
            fsp: $fsp,
            collation: $collation,
            onUpdate: $onUpdate,
        );
    }

    /**
     * @throws \Exception
     */
    private static function getSize(AbstractColumn $entityColumn, ?Column $attribute): ?int
    {
        if ($attribute?->size > 0) {
            return $attribute->size;
        }
        if ($entityColumn instanceof Columns\BoolColumn) {
            return 1;
        } elseif ($entityColumn instanceof Columns\StringColumn || $entityColumn instanceof Columns\CastableColumn) {
            return 255;
        }
        return null;
    }

    /**
     * @throws \Exception
     */
    private static function getIsNullable(AbstractColumn $entityColumn): bool
    {
        return $entityColumn->isNullable;
    }

    private static function getDefaultValue(AbstractColumn $entityColumn, ?Column $attribute): string|int|float|bool|null
    {
        if ($attribute?->default !== null) {
            if ($entityColumn instanceof Columns\BoolColumn) {
                return $attribute->default ? 1 : 0;
            }
            return $attribute->default;
        }
        if (!$entityColumn->hasDefaultValue) {
            return null;
        }

        $defaultValue = $entityColumn->defaultValue;
        if ($defaultValue === null) {
            return null;
        } elseif ($defaultValue instanceof \DateTimeInterface) {
            $unixTime = intval($defaultValue->format('U'));
            $now = time();
            if ($unixTime === $now || $unixTime === $now - 1) {
                return 'CURRENT_TIMESTAMP';
            }
        } elseif (is_bool($defaultValue)) {
            return (int)$defaultValue;
        }
        return $entityColumn->uncast($defaultValue);
    }

    private static function getValues(AbstractColumn $entityColumn): ?array
    {
        if ($entityColumn instanceof Columns\BackedStringEnumColumn) {
            /** @var \BackedEnum $enumClass */
            $enumClass = $entityColumn->type;
            return array_map(fn ($case) => $case->value, $enumClass::cases());
        } elseif ($entityColumn instanceof Columns\UnitEnumColumn) {
            /** @var \UnitEnum $enumClass */
            $enumClass = $entityColumn->type;
            return array_map(fn ($case) => $case->name, $enumClass::cases());
        }
        return null;
    }

    public function getSqlString(): string
    {
        $result = sprintf('`%s` %s', $this->name, $this->type->name);
        if ($this->size > 0) {
            $result .= "({$this->size})";
        } elseif ($this->fsp > 0) {
            $result .= "({$this->fsp})";
        } elseif ($this->values) {
            $result .= "('" . implode("','", $this->values) . "')";
        } elseif ($this->precision) {
            $result .= "(" . $this->precision;
            if ($this->scale) {
                $result .= "," . $this->scale;
            }
            $result .= ")";
        }
        if ($this->unsigned) {
            $result .= " UNSIGNED";
        }
        if ($this->collation) {
            $result .= ' COLLATE ' . $this->collation;
        }
        if (!$this->isNullable) {
            $result .= ' NOT NULL';
        } else {
            $result .= ' NULL';
        }
        $sqlDefaultValue = $this->formatSqlValue($this->defaultValue);
        if ($sqlDefaultValue !== null) {
            $result .= ' DEFAULT ' . $sqlDefaultValue;
            if ($this->defaultValue === 'CURRENT_TIMESTAMP' && $this->fsp > 0) {
                $result .= "({$this->fsp})";
            }
        }
        if ($this->isAutoincrement) {
            $result .= " AUTO_INCREMENT";
        }
        if ($this->onUpdate) {
            $result .= " ON UPDATE " . $this->onUpdate;
        }
        return $result;
    }

    private function formatSqlValue(string|int|float|bool|null $value): ?string
    {
        if (!$this->hasDefaultValue) {
            return null;
        }
        if (!$this->type->defaultValueAllowed()) {
            return $this->isNullable ? 'NULL' : null;
        }
        if ($value === null) {
            return 'NULL';
        }
        if (is_numeric($value)) {
            return (string)$value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (str_starts_with($value, "'")) {
            return $value;
        }
        if ($value === 'CURRENT_TIMESTAMP' || $value === 'NULL') {
            return $value;
        }
        return "'$value'";
    }
}