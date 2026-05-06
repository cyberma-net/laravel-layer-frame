<?php

namespace Cyberma\LayerFrame\DBStorage\Collections;

use InvalidArgumentException;

final readonly class CollectionQuery
{
    public string $valueColumn;
    public ?string $keyColumn;
    public bool $distinct;

    private function __construct(string $valueColumn, ?string $keyColumn = null, bool $distinct = false)
    {
        $valueColumn = trim($valueColumn);
        $keyColumn = $keyColumn !== null ? trim($keyColumn) : null;

        if ($valueColumn === '') {
            throw new InvalidArgumentException('Collection query value column cannot be empty.');
        }

        if ($keyColumn === '') {
            throw new InvalidArgumentException('Collection query key column cannot be empty.');
        }

        if ($keyColumn !== null && $keyColumn === $valueColumn) {
            throw new InvalidArgumentException('Collection query key and value columns must be different.');
        }

        $this->valueColumn = $valueColumn;
        $this->keyColumn = $keyColumn;
        $this->distinct = $distinct;
    }

    public static function pluck(string $valueColumn, ?string $keyColumn = null, bool $distinct = false): self
    {
        return new self($valueColumn, $keyColumn, $distinct);
    }

    public static function distinctColumn(string $column): self
    {
        return new self($column, null, true);
    }

    public static function ids(string $idColumn = 'id', bool $distinct = false): self
    {
        return new self($idColumn, null, $distinct);
    }

    public function isKeyValue(): bool
    {
        return $this->keyColumn !== null;
    }
}
