<?php declare(strict_types=1);

namespace Composite\Sync\Providers\PostgreSQL;

enum PgSQLColumnType: string
{
    case BIGINT = 'bigint';
    case BIGSERIAL = 'bigserial';
    case SERIAL = 'serial';
    case BIT = 'bit';
    case BIT_VARYING = 'bit varying';
    case VARBIT = 'varbit';
    case BOOLEAN = 'boolean';
    case BOOL = 'bool';
    case BOX = 'box';
    case BYTEA = 'bytea';
    case CHARACTER = 'character';
    case CHAR = 'char';
    case CHARACTER_VARYING = 'character varying';
    case VARCHAR = 'varchar';
    case CIDR = 'cidr';
    case CIRCLE = 'circle';
    case DATE = 'date';
    case DOUBLE_PRECISION = 'double precision';
    case FLOAT = 'float';
    case INET = 'inet';
    case INT = 'int';
    case INTEGER = 'integer';
    case INTERVAL = 'interval';
    case JSON = 'json';
    case JSONB = 'jsonb';
    case LINE = 'line';
    case LSEG = 'lseg';
    case MACADDR = 'macaddr';
    case MACADDR8 = 'macaddr8';
    case MONEY = 'money';
    case NUMERIC = 'numeric';
    case DECIMAL = 'decimal';
    case PATH = 'path';
    case PG_LSN = 'pg_lsn';
    case PG_SNAPSHOT = 'pg_snapshot';
    case POINT = 'point';
    case POLYGON = 'polygon';
    case REAL = 'real';
    case SMALLINT = 'smallint';
    case SMALLSERIAL = 'smallserial';
    case TEXT = 'text';
    case TIME = 'time';
    case TIME_WITH_TIME_ZONE = 'time with time zone';
    case TIMETZ = 'timetz';
    case TIMESTAMP = 'timestamp';
    case TIMESTAMP_WITH_TIME_ZONE = 'timestamp with time zone';
    case TIMESTAMPTZ = 'timestamptz';
    case TSQUERY = 'tsquery';
    case TSVECTOR = 'tsvector';
    case TXID_SNAPSHOT = 'txid_snapshot';
    case UUID = 'uuid';
    case XML = 'xml';

    case ENUM = 'enum';

    public function isString(): bool
    {
        return \in_array($this, [
            self::CHAR,
            self::CHARACTER,
            self::VARCHAR,
            self::CHARACTER_VARYING,
            self::TEXT,
        ]);
    }

    public function isBoolean(): bool
    {
        return \in_array($this, [
            self::BOOL,
            self::BOOLEAN,
        ]);
    }

    public function isObject(): bool
    {
        return \in_array($this, [
            self::JSON,
            self::JSONB,
        ]);
    }

    public function isDateTime(): bool
    {
        return \in_array($this, [
            self::TIMESTAMP,
            self::TIMESTAMPTZ,
            self::TIMESTAMP_WITH_TIME_ZONE,
        ]);
    }

    public function isInteger(): bool
    {
        return \in_array($this, [
            self::INT,
            self::INTEGER,
            self::SMALLINT,
            self::BIGINT,
            self::SERIAL,
            self::SMALLSERIAL,
            self::BIGSERIAL,
            self::BIGSERIAL,
        ]);
    }

    public function isFloat(): bool
    {
        return \in_array($this, [
            self::FLOAT,
            self::NUMERIC,
            self::DECIMAL,
            self::DOUBLE_PRECISION,
        ]);
    }
}