<?php

namespace Cyberma\LayerFrame\Tests\Unit\DBStorage;

use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\DBStorage\Collections\CollectionQuery;
use Cyberma\LayerFrame\DBStorage\Columns\ColumnQuery;
use Cyberma\LayerFrame\DBStorage\DBStorage;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class DBStorageColumnCollectionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_column_collection_returns_flat_array(): void
    {
        $builder = new FakeColumnCollectionBuilder(['a@example.com', 'b@example.com']);
        $storage = $this->makeStorage($builder);

        $result = $storage->columnCollection(ColumnQuery::pluck('email'));

        $this->assertSame(['a@example.com', 'b@example.com'], $result);
    }

    public function test_column_collection_returns_key_value_map(): void
    {
        $builder = new FakeColumnCollectionBuilder([
            10 => 'john@example.com',
            20 => 'jane@example.com',
        ]);
        $storage = $this->makeStorage($builder);

        $result = $storage->columnCollection(ColumnQuery::pluck('email', 'id'));

        $this->assertSame([
            10 => 'john@example.com',
            20 => 'jane@example.com',
        ], $result);
    }

    public function test_collection_query_supports_distinct_column_and_ids(): void
    {
        $builder = new FakeColumnCollectionBuilder([1, 2, 3]);
        $storage = $this->makeStorage($builder);

        $distinctStatuses = $storage->collection(CollectionQuery::distinctColumn('status'));
        $ids = $storage->collection(CollectionQuery::ids());

        $this->assertSame([1, 2, 3], $distinctStatuses);
        $this->assertSame([1, 2, 3], $ids);
    }

    public function test_column_collection_does_not_mutate_original_query_builder_state(): void
    {
        $builder = new FakeColumnCollectionBuilder(['x']);
        $builder->columns = ['id', 'email'];
        $builder->orders = [['column' => 'id', 'direction' => 'desc']];

        $columnsBefore = $builder->columns;
        $ordersBefore = $builder->orders;

        $storage = $this->makeStorage($builder);
        $storage->columnCollection(ColumnQuery::pluck('email', null, true));

        $this->assertSame($columnsBefore, $builder->columns);
        $this->assertSame($ordersBefore, $builder->orders);
        $this->assertFalse($builder->distinctApplied);
    }

    private function makeStorage(Builder $builder): DBStorage
    {
        /** @var IModelMap $modelMap */
        $modelMap = Mockery::mock(IModelMap::class);

        return new class($modelMap, $builder) extends DBStorage {
            private Builder $builder;

            public function __construct(IModelMap $modelMap, Builder $builder)
            {
                parent::__construct($modelMap);
                $this->builder = $builder;
            }

            public function queryByConditions(array $conditions, array $columnNames = []): Builder
            {
                return $this->builder;
            }
        };
    }
}

final class FakeColumnCollectionBuilder extends Builder
{
    /** @var array<int|string, mixed> */
    private array $pluckResult;
    public bool $distinctApplied = false;

    /**
     * @param array<int|string, mixed> $pluckResult
     */
    public function __construct(array $pluckResult)
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        parent::__construct($connection, new Grammar(), new Processor());
        $this->pluckResult = $pluckResult;
    }

    public function distinct()
    {
        $this->distinctApplied = true;

        return $this;
    }

    public function pluck($column, $key = null): Collection
    {
        return new Collection($this->pluckResult);
    }
}
