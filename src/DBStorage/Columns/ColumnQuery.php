<?php

namespace Cyberma\LayerFrame\DBStorage\Columns;

use InvalidArgumentException;

final readonly class ColumnQuery
{
    public string $valueColumn;
    public ?string $keyColumn;
    public bool $distinct;

    private function __construct(string $valueColumn, ?string $keyColumn = null, bool $distinct = false)
    {
        $valueColumn = trim($valueColumn);
        $keyColumn = $keyColumn !== null ? trim($keyColumn) : null;

        if ($valueColumn === '') {
            throw new InvalidArgumentException('Column query value column cannot be empty.');
        }

        if ($keyColumn === '') {
            throw new InvalidArgumentException('Column query key column cannot be empty.');
        }

        if ($keyColumn !== null && $keyColumn === $valueColumn) {
            throw new InvalidArgumentException('Column query key and value columns must be different.');
        }

        $this->valueColumn = $valueColumn;
        $this->keyColumn = $keyColumn;
        $this->distinct = $distinct;
    }

    public static function pluck(string $valueColumn, ?string $keyColumn = null, bool $distinct = false): self
    {
        return new self($valueColumn, $keyColumn, $distinct);
    }

    public function isKeyValue(): bool
    {
        return $this->keyColumn !== null;
    }
}
