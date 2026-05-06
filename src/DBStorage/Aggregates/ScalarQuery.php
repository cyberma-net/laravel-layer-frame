<?php

namespace Cyberma\LayerFrame\DBStorage\Aggregates;

use InvalidArgumentException;

final readonly class ScalarQuery
{
    public AggregateType $type;
    public ?string $column;
    public ?string $alias;
    public bool $distinct;

    private function __construct(AggregateType $type, ?string $column = null, ?string $alias = null, bool $distinct = false)
    {
        $column = $column !== null ? trim($column) : null;
        $alias = $alias !== null ? trim($alias) : null;

        if ($column === '') {
            throw new InvalidArgumentException('Scalar query column cannot be empty.');
        }

        if ($alias === '') {
            throw new InvalidArgumentException('Scalar query alias cannot be empty.');
        }

        if ($type->requiresColumn() && $column === null) {
            throw new InvalidArgumentException(
                sprintf('Aggregate type "%s" requires a target column.', $type->value)
            );
        }

        if (!$type->supportsColumn() && $column !== null) {
            throw new InvalidArgumentException(
                sprintf('Aggregate type "%s" does not support a target column.', $type->value)
            );
        }

        $this->type = $type;
        $this->column = $column;
        $this->alias = $alias;
        $this->distinct = $distinct;
    }

    public static function make(AggregateType $type, ?string $column = null, ?string $alias = null, bool $distinct = false): self
    {
        return new self($type, $column, $alias, $distinct);
    }

    public static function count(?string $column = null, ?string $alias = null, bool $distinct = false): self
    {
        return new self(AggregateType::COUNT, $column, $alias, $distinct);
    }

    public static function sum(string $column, ?string $alias = null, bool $distinct = false): self
    {
        return new self(AggregateType::SUM, $column, $alias, $distinct);
    }

    public static function avg(string $column, ?string $alias = null, bool $distinct = false): self
    {
        return new self(AggregateType::AVG, $column, $alias, $distinct);
    }

    public static function min(string $column, ?string $alias = null, bool $distinct = false): self
    {
        return new self(AggregateType::MIN, $column, $alias, $distinct);
    }

    public static function max(string $column, ?string $alias = null, bool $distinct = false): self
    {
        return new self(AggregateType::MAX, $column, $alias, $distinct);
    }

    public static function value(string $column, ?string $alias = null, bool $distinct = false): self
    {
        return new self(AggregateType::VALUE, $column, $alias, $distinct);
    }

    public static function exists(?string $alias = null): self
    {
        return new self(AggregateType::EXISTS, null, $alias, false);
    }
}
