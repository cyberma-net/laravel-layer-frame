<?php

namespace Cyberma\LayerFrame\Tests\Unit\DBStorage;

use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\DBStorage\Aggregates\ScalarQuery;
use Cyberma\LayerFrame\DBStorage\DBStorage;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PHPUnit\Framework\TestCase;

class DBStorageScalarTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_scalar_count(): void
    {
        $builder = new FakeScalarBuilder(['count' => 2]);
        $storage = $this->makeStorage($builder);

        $result = $storage->scalar(ScalarQuery::count());

        $this->assertSame(2, $result);
    }

    public function test_scalar_distinct_count_for_column(): void
    {
        $builder = new FakeScalarBuilder(['count' => 5]);
        $storage = $this->makeStorage($builder);

        $result = $storage->scalar(ScalarQuery::count('user_id', null, true));

        $this->assertSame(5, $result);
        $this->assertSame('user_id', $builder->tracker->lastCountColumns);
    }

    public function test_scalar_exists(): void
    {
        $storageTrue = $this->makeStorage(new FakeScalarBuilder(['exists' => true]));
        $storageFalse = $this->makeStorage(new FakeScalarBuilder(['exists' => false]));

        $this->assertTrue($storageTrue->scalar(ScalarQuery::exists()));
        $this->assertFalse($storageFalse->scalar(ScalarQuery::exists()));
    }

    public function test_scalar_value(): void
    {
        $storage = $this->makeStorage(new FakeScalarBuilder(['value' => 'a@example.com']));
        $result = $storage->scalar(ScalarQuery::value('email'));

        $this->assertSame('a@example.com', $result);
    }

    public function test_scalar_sum_and_avg(): void
    {
        $storage = $this->makeStorage(new FakeScalarBuilder(['sum' => 30, 'avg' => 4.0, 'min' => 1, 'max' => 9]));

        $sum = $storage->scalar(ScalarQuery::sum('price'));
        $avg = $storage->scalar(ScalarQuery::avg('score'));
        $min = $storage->scalar(ScalarQuery::min('score'));
        $max = $storage->scalar(ScalarQuery::max('score'));

        $this->assertSame(30, $sum);
        $this->assertSame(4.0, $avg);
        $this->assertSame(1, $min);
        $this->assertSame(9, $max);
    }

    public function test_scalar_null_handling_and_empty_results(): void
    {
        $storageNullValue = $this->makeStorage(new FakeScalarBuilder(['value' => null]));
        $storageEmpty = $this->makeStorage(new FakeScalarBuilder(['value' => null, 'avg' => null]));

        $this->assertNull($storageNullValue->scalar(ScalarQuery::value('email')));
        $this->assertNull($storageEmpty->scalar(ScalarQuery::value('email')));
        $this->assertNull($storageEmpty->scalar(ScalarQuery::avg('score')));
    }

    public function test_scalar_execution_does_not_mutate_original_query_builder_state(): void
    {
        $builder = new FakeScalarBuilder(['count' => 3]);
        $builder->columns = ['id', 'email'];
        $builder->orders = [['column' => 'id', 'direction' => 'desc']];

        $columnsBefore = $builder->columns;
        $ordersBefore = $builder->orders;

        $storage = $this->makeStorage($builder);
        $storage->scalar(ScalarQuery::count());

        $this->assertSame($columnsBefore, $builder->columns);
        $this->assertSame($ordersBefore, $builder->orders);
        $this->assertFalse($builder->distinctApplied);
    }

    public function test_backward_compatibility_wrappers_count_and_exists(): void
    {
        $storageCount = $this->makeStorage(new FakeScalarBuilder(['count' => 11]));
        $storageExists = $this->makeStorage(new FakeScalarBuilder(['exists' => true]));

        $this->assertSame(11, $storageCount->countByConditions([['status', '=', 'active']]));
        $this->assertTrue($storageExists->existsByConditions([['status', '=', 'active']]));
    }

    public function test_execute_scalar_by_conditions_supports_string_operation_and_invalid_conditions_fail(): void
    {
        $builder = new FakeScalarBuilder(['count' => 2]);
        $storage = $this->makeNativeStorage($builder);

        $this->assertSame(2, $storage->executeScalarByConditions([['status', '=', 'active']], 'count'));

        $this->expectException(\InvalidArgumentException::class);
        $storage->executeScalarByConditions(['status'], 'count');
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

    private function makeNativeStorage(Builder $builder): DBStorage
    {
        /** @var IModelMap $modelMap */
        $modelMap = Mockery::mock(IModelMap::class);
        $modelMap->shouldReceive('getTable')->andReturn('users');
        $modelMap->shouldReceive('hasSoftDeletes')->andReturn(false);

        return new class($modelMap, $builder) extends DBStorage {
            private Builder $builder;

            public function __construct(IModelMap $modelMap, Builder $builder)
            {
                parent::__construct($modelMap);
                $this->builder = $builder;
            }

            public function table(array $columnsNames = []): Builder
            {
                return $this->builder;
            }
        };
    }
}

final class FakeScalarBuilder extends Builder
{
    /** @var array<string, int|float|string|bool|null> */
    private array $results;
    public bool $distinctApplied = false;
    public \stdClass $tracker;

    /**
     * @param array<string, int|float|string|bool|null> $results
     */
    public function __construct(array $results = [])
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        parent::__construct($connection, new Grammar(), new Processor());
        $this->results = $results;
        $this->tracker = (object)[
            'lastCountColumns' => null,
            'distinctApplied' => false,
        ];
    }

    public function distinct()
    {
        $this->distinctApplied = true;
        $this->tracker->distinctApplied = true;

        return $this;
    }

    public function count($columns = '*')
    {
        $this->tracker->lastCountColumns = $columns;
        return $this->results['count'] ?? 0;
    }

    public function exists()
    {
        return $this->results['exists'] ?? false;
    }

    public function value($column)
    {
        return $this->results['value'] ?? null;
    }

    public function sum($column)
    {
        return $this->results['sum'] ?? 0;
    }

    public function avg($column)
    {
        return $this->results['avg'] ?? null;
    }

    public function min($column)
    {
        return $this->results['min'] ?? null;
    }

    public function max($column)
    {
        return $this->results['max'] ?? null;
    }
}
