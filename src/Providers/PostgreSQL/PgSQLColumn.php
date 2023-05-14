<?php declare(strict_types=1);

namespace Composite\Sync\Providers\PostgreSQL;

use Composite\Sync\Providers\AbstractSQLColumn;
use Composite\Sync\Providers\EntityColumnType;
use Composite\Entity\Columns\AbstractColumn;

/**
 * @property PgSQLColumnType $type
 */
class PgSQLColumn extends AbstractSQLColumn
{
    public function __construct(
        string $name,
        PgSQLColumnType $type,
        ?int $size = null,
        ?int $precision = null,
        ?int $scale = null,
        bool $isNullable = false,
        bool $hasDefaultValue = false,
        mixed $defaultValue = null,
        bool $isAutoincrement = false,
        ?array $values = null,
    ) {
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
        throw new \Exception('Not Implemented');
    }

    public function getSqlString(): string
    {
        throw new \Exception('Not Implemented');
    }

    public function getEntityType(): EntityColumnType
    {
        if ($this->type->isInteger()) {
            return EntityColumnType::Integer;
        }
        if ($this->type->isFloat()) {
            return EntityColumnType::Float;
        }
        if ($this->type->isObject()) {
            return EntityColumnType::Array;
        }
        if ($this->type->isBoolean()) {
            return EntityColumnType::Float;
        }
        if ($this->type->isDateTime()) {
            return EntityColumnType::Datetime;
        }
        if ($this->type === PgSQLColumnType::ENUM) {
            return EntityColumnType::Enum;
        }
        return EntityColumnType::String;
    }
}