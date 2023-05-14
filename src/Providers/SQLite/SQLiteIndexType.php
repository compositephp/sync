<?php declare(strict_types=1);

namespace Composite\Sync\Providers\SQLite;

enum SQLiteIndexType
{
    case INDEX;
    case PRIMARY;
    case UNIQUE;

    /**
     * @throws \Exception
     */
    public static function fromString(string $type): self
    {
        $typeUpper = strtoupper($type);
        foreach (self::cases() as $case) {
            if ($case->name === $typeUpper) {
                return $case;
            }
        }
        throw new \Exception(sprintf("Index type `%s` is not supported", $type));
    }
}