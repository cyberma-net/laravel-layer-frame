<?php

namespace Cyberma\LayerFrame\DBStorage\Mutations;

use InvalidArgumentException;

final readonly class MutationQuery
{
    public MutationType $type;
    public array $values;
    public ?string $column;
    public int|float $amount;
    public int $limit;
    public bool $permanentDelete;

    private function __construct(
        MutationType $type,
        array $values = [],
        ?string $column = null,
        int|float $amount = 1,
        int $limit = PHP_INT_MAX,
        bool $permanentDelete = false
    ) {
        $column = $column !== null ? trim($column) : null;

        if ($column === '') {
            throw new InvalidArgumentException('Mutation query column cannot be empty.');
        }

        if (($type === MutationType::UPDATE) && $values === []) {
            throw new InvalidArgumentException('Mutation update query requires at least one value.');
        }

        if (($type === MutationType::INCREMENT || $type === MutationType::DECREMENT) && $column === null) {
            throw new InvalidArgumentException(sprintf('Mutation "%s" requires target column.', $type->value));
        }

        if (($type === MutationType::INCREMENT || $type === MutationType::DECREMENT) && $amount <= 0) {
            throw new InvalidArgumentException('Mutation increment/decrement amount must be greater than zero.');
        }

        if ($type !== MutationType::DELETE && $limit !== PHP_INT_MAX) {
            throw new InvalidArgumentException('Mutation limit is supported only for delete.');
        }

        $this->type = $type;
        $this->values = $values;
        $this->column = $column;
        $this->amount = $amount;
        $this->limit = $limit;
        $this->permanentDelete = $permanentDelete;
    }

    public static function update(array $values): self
    {
        return new self(MutationType::UPDATE, $values);
    }

    public static function delete(int $limit = PHP_INT_MAX, bool $permanentDelete = false): self
    {
        return new self(MutationType::DELETE, [], null, 1, $limit, $permanentDelete);
    }

    public static function increment(string $column, int|float $amount = 1, array $values = []): self
    {
        return new self(MutationType::INCREMENT, $values, $column, $amount);
    }

    public static function decrement(string $column, int|float $amount = 1, array $values = []): self
    {
        return new self(MutationType::DECREMENT, $values, $column, $amount);
    }
}
