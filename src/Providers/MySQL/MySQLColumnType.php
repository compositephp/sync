<?php declare(strict_types=1);

namespace Composite\Sync\Providers\MySQL;

use Composite\Entity\Columns;
use Composite\Entity\Columns\AbstractColumn;

enum MySQLColumnType
{
    case INT;
    case SMALLINT;
    case MEDIUMINT;
    case BIGINT;
    case TINYINT;
    case BIT;
    case FLOAT;
    case REAL;
    case DOUBLE;
    case DECIMAL;
    case NUMERIC;
    case CHAR;
    case VARCHAR;
    case VARBINARY;
    case TEXT;
    case TINYTEXT;
    case MEDIUMTEXT;
    case LONGTEXT;
    case BLOB;
    case MEDIUMBLOB;
    case LONGBLOB;
    case ENUM;
    case SET;
    case JSON;
    case TIMESTAMP;
    case DATETIME;
    case DATE;
    case TIME;
    case YEAR;
    case BINARY;
    case TINYBLOB;
    case POINT;
    case LINESTRING;
    case POLYGON;

    case GEOMETRY;

    case MULTILINESTRING;

    case MULTIPOINT;

    case MULTIPOLYGON;

    case GEOMETRYCOLLECTION;

    case BOOL;

    case DEC;

    case FIXED;

    public static function fromString(string $name): self
    {
        $nameUpper = strtoupper($name);
        foreach (self::cases() as $case) {
            if ($case->name === $nameUpper) {
                return $case;
            }
        }
        throw new \Exception("Case `$name` not found in enum " . self::class);
    }

    /**
     * @throws \Exception
     */
    public static function fromEntityColumn(AbstractColumn $column): self
    {
        return match($column::class) {
            Columns\BoolColumn::class => self::TINYINT,
            Columns\FloatColumn::class => self::FLOAT,
            Columns\DateTimeColumn::class => self::TIMESTAMP,
            Columns\IntegerColumn::class, Columns\BackedIntEnumColumn::class => self::INT,
            Columns\StringColumn::class, Columns\UuidColumn::class, Columns\CastableColumn::class => self::VARCHAR,
            Columns\BackedStringEnumColumn::class, Columns\UnitEnumColumn::class => self::ENUM,
            Columns\ArrayColumn::class, Columns\ObjectColumn::class, Columns\EntityColumn::class, Columns\EntityListColumn::class => self::JSON,
            default => throw new \Exception(sprintf("Column class `%s` is not supported", $column::class)),
        };
    }

    public function isString(): bool
    {
        return \in_array($this, [
            self::VARCHAR,
            self::CHAR,
            self::TEXT,
            self::TINYTEXT,
            self::MEDIUMTEXT,
            self::LONGTEXT,
        ]);
    }

    public function hasCollation(): bool
    {
        return $this->isString() || $this->isEnum();
    }

    public function isInteger(): bool
    {
        return \in_array($this, [
            self::INT,
            self::TINYINT,
            self::SMALLINT,
            self::MEDIUMINT,
            self::BIGINT,
        ]);
    }

    public function isFloat(): bool
    {
        return \in_array($this, [
            self::FLOAT,
            self::DECIMAL,
            self::DOUBLE,
            self::REAL,
            self::DEC,
            self::FIXED,
            self::NUMERIC,
        ]);
    }

    public function isDateTime(): bool
    {
        return \in_array($this, [
            self::DATETIME,
            self::TIMESTAMP,
        ]);
    }

    public function isEnum(): bool
    {
        return $this === self::ENUM;
    }

    public function defaultValueAllowed(): bool
    {
        return !\in_array($this, [
            self::JSON,
            self::BLOB,
            self::LONGBLOB,
            self::TEXT,
            self::LONGTEXT,
            self::GEOMETRY,
            self::GEOMETRYCOLLECTION,
        ]);
    }
}