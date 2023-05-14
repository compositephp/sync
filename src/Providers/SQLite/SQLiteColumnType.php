<?php declare(strict_types=1);

namespace Composite\Sync\Providers\SQLite;

enum SQLiteColumnType: string
{
    case INT = 'INT';
    case INTEGER = 'INTEGER';
    case TINYINT = 'TINYINT';
    case SMALLINT = 'SMALLINT';
    case MEDIUMINT = 'MEDIUMINT';
    case BIGINT = 'BIGINT';
    case UNSIGNED_BIG_INT = 'UNSIGNED BIG INT';
    case INT2 = 'INT2';
    case INT8 = 'INT8';
    case CHARACTER = 'CHARACTER';
    case VARCHAR = 'VARCHAR';
    case VARYING_CHARACTER = 'VARYING CHARACTER';
    case NCHAR = 'NCHAR';
    case NATIVE_CHARACTER = 'NATIVE CHARACTER';
    case NVARCHAR = 'NVARCHAR';
    case TEXT = 'TEXT';
    case CLOB = 'CLOB';
    case BLOB = 'BLOB';
    case REAL = 'REAL';
    case DOUBLE = 'DOUBLE';
    case DOUBLE_PRECISION = 'DOUBLE PRECISION';
    case FLOAT = 'FLOAT';
    case NUMERIC = 'NUMERIC';
    case DECIMAL = 'DECIMAL';
    case BOOLEAN = 'BOOLEAN';
    case DATE = 'DATE';
    case DATETIME = 'DATETIME';
    case TIMESTAMP = 'TIMESTAMP';

    public function isString(): bool
    {
        return \in_array($this, [
            self::VARCHAR,
            self::CHARACTER,
            self::VARYING_CHARACTER,
            self::NCHAR,
            self::NATIVE_CHARACTER,
            self::NVARCHAR,
            self::TEXT,
        ]);
    }

    public function isFloat(): bool
    {
        return \in_array($this, [
            self::REAL,
            self::FLOAT,
            self::DOUBLE,
            self::DOUBLE_PRECISION,
            self::DECIMAL,
        ]);
    }

    public function isDateTime(): bool
    {
        return \in_array($this, [
            self::DATETIME,
            self::TIMESTAMP,
        ]);
    }

    public function isBoolean(): bool
    {
        return $this === self::BOOLEAN;
    }

    public function isInteger(): bool
    {
        return \in_array($this, [
            self::INT,
            self::INTEGER,
            self::INT2,
            self::INT8,
            self::BIGINT,
            self::UNSIGNED_BIG_INT,
            self::TINYINT,
            self::SMALLINT,
            self::MEDIUMINT,
        ]);
    }
}