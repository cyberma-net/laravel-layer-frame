<?php

namespace Cyberma\LayerFrame\Tests\Unit\DBStorage;

use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\DBStorage\Aggregates\AggregateType;
use Cyberma\LayerFrame\DBStorage\Aggregates\ScalarQuery;
use Cyberma\LayerFrame\DBStorage\DBStorage;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Tests for condition normalization, operator handling, empty IN safety,
 * and scalar short-circuit behavior in DBStorage.
 */
class DBStorageConditionsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // normalizeSingleCondition
    // -------------------------------------------------------------------------

    public function test_normalize_single_two_element_scalar_becomes_equals(): void
    {
        $storage = $this->makeStorage();

        $this->assertSame(['status', '=', 'active'], $storage->normalizeSingleCondition(['status', 'active']));
        $this->assertSame(['userId', '=', 5], $storage->normalizeSingleCondition(['userId', 5]));
        $this->assertSame(['name', '=', null], $storage->normalizeSingleCondition(['name', null]));
    }

    public function test_normalize_single_two_element_array_value_becomes_in(): void
    {
        $storage = $this->makeStorage();

        $result = $storage->normalizeSingleCondition(['status', ['pending', 'running']]);
        $this->assertSame(['status', 'in', ['pending', 'running']], $result);
    }

    public function test_normalize_single_two_element_empty_array_value_becomes_in_with_empty(): void
    {
        $storage = $this->makeStorage();

        $result = $storage->normalizeSingleCondition(['status', []]);
        $this->assertSame(['status', 'in', []], $result);
    }

    public function test_normalize_single_three_element_passes_through(): void
    {
        $storage = $this->makeStorage();

        $this->assertSame(['status', '=', 'active'], $storage->normalizeSingleCondition(['status', '=', 'active']));
        $this->assertSame(['price', '>=', 100], $storage->normalizeSingleCondition(['price', '>=', 100]));
        $this->assertSame(['id', 'in', [1, 2, 3]], $storage->normalizeSingleCondition(['id', 'in', [1, 2, 3]]));
    }

    public function test_normalize_single_invalid_format_throws(): void
    {
        $storage = $this->makeStorage();

        $this->expectException(\InvalidArgumentException::class);
        $storage->normalizeSingleCondition(['col', 'op', 'val', 'extra']);
    }

    public function test_normalize_conditions_handles_multi_condition_array_with_array_values(): void
    {
        $storage = $this->makeStorage();

        $result = $storage->normalizeConditions([
            ['status', ['pending', 'running']],
            ['userId', 5],
        ]);

        $this->assertSame([
            ['status', 'in', ['pending', 'running']],
            ['userId', '=', 5],
        ], $result);
    }

    public function test_normalize_conditions_single_condition_shorthand(): void
    {
        $storage = $this->makeStorage();

        // Single condition shorthand: ['column', 'value'] → treated as single condition
        $result = $storage->normalizeConditions(['status', 'active']);
        $this->assertSame([['status', '=', 'active']], $result);
    }

    // -------------------------------------------------------------------------
    // prepareQueryWhere – operator synonyms
    // -------------------------------------------------------------------------

    public function test_operator_null_variants_apply_where_null(): void
    {
        $storage = $this->makeStorageWithTable();

        foreach (['null', 'is null', 'is_null', 'isnull', 'NULL', 'IS NULL'] as $op) {
            $builder = $this->makeBuilder();
            $storage->prepareQueryWhere($builder, 'deleted_at', null, $op);

            // Laravel 13 uses type='Null' (no separate 'not' key; NOT NULL uses type='NotNull')
            $nullWhere = $this->findWhere($builder->wheres, 'Null');
            $this->assertNotNull($nullWhere, "Expected a Null where for operator '{$op}'");
        }
    }

    public function test_operator_not_null_variants_apply_where_not_null(): void
    {
        $storage = $this->makeStorageWithTable();

        foreach (['not null', 'is not null', 'not_null', 'notnull', 'NOT NULL', 'NOT_NULL', 'NOTNULL'] as $op) {
            $builder = $this->makeBuilder();
            $storage->prepareQueryWhere($builder, 'deleted_at', null, $op);

            // Laravel 13 uses type='NotNull' for whereNotNull (no shared 'not' key)
            $notNullWhere = $this->findWhere($builder->wheres, 'NotNull');
            $this->assertNotNull($notNullWhere, "Expected a NotNull where for operator '{$op}'");
        }
    }

    public function test_operator_bang_equals_normalized_to_not_equal(): void
    {
        $storage = $this->makeStorageWithTable();
        $builder = $this->makeBuilder();

        $storage->prepareQueryWhere($builder, 'status', 'active', '!=');

        $basicWhere = $this->findWhere($builder->wheres, 'Basic');
        $this->assertNotNull($basicWhere);
        $this->assertSame('<>', $basicWhere['operator']);
    }

    public function test_operator_not_in_variants_resolve_consistently(): void
    {
        $storage = $this->makeStorageWithTable();

        foreach (['not in', 'NOT IN', 'notin', 'not_in'] as $op) {
            $builder = $this->makeBuilder();
            $storage->prepareQueryWhere($builder, 'status', ['a', 'b'], $op);

            // Laravel 13 uses type='NotIn' for whereNotIn (no shared 'not' key)
            $notInWhere = $this->findWhere($builder->wheres, 'NotIn');
            $this->assertNotNull($notInWhere, "Expected NotIn where for operator '{$op}'");
        }
    }

    public function test_operator_in_variants_resolve_consistently(): void
    {
        $storage = $this->makeStorageWithTable();

        foreach (['in', 'IN', 'In'] as $op) {
            $builder = $this->makeBuilder();
            $storage->prepareQueryWhere($builder, 'status', ['a', 'b'], $op);

            // Laravel 13 uses type='In' for whereIn
            $inWhere = $this->findWhere($builder->wheres, 'In');
            $this->assertNotNull($inWhere, "Expected In where for operator '{$op}'");
        }
    }

    // -------------------------------------------------------------------------
    // prepareQueryWhere – empty IN / NOT IN
    // -------------------------------------------------------------------------

    public function test_empty_in_array_adds_explicit_false_raw_predicate(): void
    {
        $storage = $this->makeStorageWithTable();
        $builder = $this->makeBuilder();

        $storage->prepareQueryWhere($builder, 'id', [], 'in');

        $rawWhere = $this->findWhere($builder->wheres, 'raw');
        $this->assertNotNull($rawWhere, 'Expected a raw where clause for empty IN.');
        $this->assertStringContainsString('0 = 1', $rawWhere['sql']);
    }

    public function test_empty_not_in_array_adds_no_constraint(): void
    {
        $storage = $this->makeStorageWithTable();
        $builder = $this->makeBuilder();

        $storage->prepareQueryWhere($builder, 'id', [], 'not in');

        // No where clauses added — "NOT IN empty set" = all rows pass.
        $this->assertEmpty($builder->wheres);
    }

    public function test_non_array_in_value_throws(): void
    {
        $storage = $this->makeStorageWithTable();
        $builder = $this->makeBuilder();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IN operator requires array value');
        $storage->prepareQueryWhere($builder, 'status', 'active', 'in');
    }

    public function test_non_array_not_in_value_throws(): void
    {
        $storage = $this->makeStorageWithTable();
        $builder = $this->makeBuilder();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NOT IN operator requires array value');
        $storage->prepareQueryWhere($builder, 'status', 'active', 'not in');
    }

    // -------------------------------------------------------------------------
    // executeScalarByConditions – short-circuit on empty IN
    // -------------------------------------------------------------------------

    public function test_scalar_exists_short_circuits_to_false_on_empty_in(): void
    {
        $storage = $this->makeShortCircuitStorage();

        $result = $storage->executeScalarByConditions([['id', 'in', []]], AggregateType::EXISTS);

        $this->assertFalse($result);
        $this->assertFalse($storage->queryByConditionsCalled, 'queryByConditions must not be called when short-circuiting.');
    }

    public function test_scalar_count_short_circuits_to_zero_on_empty_in(): void
    {
        $storage = $this->makeShortCircuitStorage();

        $result = $storage->executeScalarByConditions([['status', 'in', []]], AggregateType::COUNT);

        $this->assertSame(0, $result);
        $this->assertFalse($storage->queryByConditionsCalled);
    }

    public function test_scalar_sum_short_circuits_to_zero_on_empty_in(): void
    {
        $storage = $this->makeShortCircuitStorage();

        $result = $storage->executeScalarByConditions([['id', 'in', []]], AggregateType::SUM, 'amount');

        $this->assertSame(0, $result);
        $this->assertFalse($storage->queryByConditionsCalled);
    }

    public function test_scalar_avg_short_circuits_to_null_on_empty_in(): void
    {
        $storage = $this->makeShortCircuitStorage();

        $result = $storage->executeScalarByConditions([['id', 'in', []]], AggregateType::AVG, 'price');

        $this->assertNull($result);
        $this->assertFalse($storage->queryByConditionsCalled);
    }

    public function test_scalar_min_max_value_short_circuit_to_null_on_empty_in(): void
    {
        $storage = $this->makeShortCircuitStorage();

        $this->assertNull($storage->executeScalarByConditions([['id', 'in', []]], AggregateType::MIN, 'price'));
        $this->assertNull($storage->executeScalarByConditions([['id', 'in', []]], AggregateType::MAX, 'price'));
        $this->assertNull($storage->executeScalarByConditions([['id', 'in', []]], AggregateType::VALUE, 'email'));
    }

    public function test_short_circuit_fires_on_empty_in_in_multi_condition_set(): void
    {
        $storage = $this->makeShortCircuitStorage();

        // Even with other valid conditions present, an empty IN short-circuits the whole operation.
        $result = $storage->executeScalarByConditions([
            ['userId', '=', 5],
            ['id', 'in', []],
            ['status', '=', 'active'],
        ], AggregateType::COUNT);

        $this->assertSame(0, $result);
        $this->assertFalse($storage->queryByConditionsCalled);
    }

    public function test_short_circuit_fires_on_array_value_two_element_condition(): void
    {
        $storage = $this->makeShortCircuitStorage();

        // Short form ['id', []] auto-detects as IN with empty array.
        $result = $storage->executeScalarByConditions([['id', []]], AggregateType::EXISTS);

        $this->assertFalse($result);
        $this->assertFalse($storage->queryByConditionsCalled);
    }

    public function test_no_short_circuit_when_empty_conditions(): void
    {
        $fakeBuilder = new FakeConditionsScalarBuilder(['count' => 7]);
        $storage = $this->makeStorageWithFakeBuilder($fakeBuilder);

        $result = $storage->executeScalarByConditions([], AggregateType::COUNT);

        $this->assertSame(7, $result);
    }

    public function test_no_short_circuit_for_non_empty_in_array(): void
    {
        $fakeBuilder = new FakeConditionsScalarBuilder(['exists' => true]);
        $storage = $this->makeStorageWithFakeBuilder($fakeBuilder);

        $result = $storage->executeScalarByConditions([['id', 'in', [1, 2, 3]]], AggregateType::EXISTS);

        $this->assertTrue($result);
    }

    public function test_not_in_with_empty_array_does_not_short_circuit(): void
    {
        $fakeBuilder = new FakeConditionsScalarBuilder(['count' => 10]);
        $storage = $this->makeStorageWithFakeBuilder($fakeBuilder);

        // NOT IN [] = no constraint = all rows pass → should execute normally, not short-circuit.
        $result = $storage->executeScalarByConditions([['status', 'not in', []]], AggregateType::COUNT);

        $this->assertSame(10, $result);
    }

    // -------------------------------------------------------------------------
    // scalar() API – return type contract
    // -------------------------------------------------------------------------

    public function test_scalar_exists_always_returns_bool(): void
    {
        $storageTrue = $this->makeStorageWithFakeBuilder(new FakeConditionsScalarBuilder(['exists' => true]));
        $storageFalse = $this->makeStorageWithFakeBuilder(new FakeConditionsScalarBuilder(['exists' => false]));

        $this->assertIsBool($storageTrue->scalar(ScalarQuery::exists()));
        $this->assertIsBool($storageFalse->scalar(ScalarQuery::exists()));
    }

    public function test_scalar_count_always_returns_int(): void
    {
        $storage = $this->makeStorageWithFakeBuilder(new FakeConditionsScalarBuilder(['count' => 0]));

        $this->assertIsInt($storage->scalar(ScalarQuery::count()));
    }

    public function test_scalar_sum_returns_int_or_zero_for_empty_result(): void
    {
        $storage = $this->makeStorageWithFakeBuilder(new FakeConditionsScalarBuilder(['sum' => 0]));

        $result = $storage->scalar(ScalarQuery::sum('amount'));
        $this->assertIsInt($result);
        $this->assertSame(0, $result);
    }

    // -------------------------------------------------------------------------
    // Query state isolation
    // -------------------------------------------------------------------------

    public function test_scalar_execution_does_not_mutate_original_builder_state(): void
    {
        $fakeBuilder = new FakeConditionsScalarBuilder(['count' => 1]);
        $fakeBuilder->columns = ['id', 'status'];
        $fakeBuilder->orders = [['column' => 'id', 'direction' => 'desc']];

        $storage = $this->makeStorageWithFakeBuilder($fakeBuilder);
        $storage->scalar(ScalarQuery::count());

        $this->assertSame(['id', 'status'], $fakeBuilder->columns);
        $this->assertSame([['column' => 'id', 'direction' => 'desc']], $fakeBuilder->orders);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeStorage(): DBStorage
    {
        $modelMap = Mockery::mock(IModelMap::class);

        return new DBStorage($modelMap);
    }

    private function makeStorageWithTable(): DBStorage
    {
        $modelMap = Mockery::mock(IModelMap::class);
        $modelMap->shouldReceive('getTable')->andReturn('test_table');
        $modelMap->shouldReceive('hasSoftDeletes')->andReturn(false);

        return new DBStorage($modelMap);
    }

    private function makeBuilder(): Builder
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $grammar = Mockery::mock(Grammar::class);
        $grammar->shouldReceive('getOperators')->andReturn(['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'between', 'ilike', 'not ilike']);
        $grammar->shouldReceive('getBitwiseOperators')->andReturn([]);

        return new Builder($connection, $grammar, new Processor());
    }

    /**
     * Returns a DBStorage that tracks whether queryByConditions was called.
     * Used to verify the short-circuit fires without touching the DB layer.
     */
    private function makeShortCircuitStorage(): DBStorage
    {
        $modelMap = Mockery::mock(IModelMap::class);
        $modelMap->shouldReceive('getTable')->andReturn('test_table');
        $modelMap->shouldReceive('hasSoftDeletes')->andReturn(false);

        return new class($modelMap) extends DBStorage {
            public bool $queryByConditionsCalled = false;

            public function queryByConditions(array $conditions, array $columnNames = []): Builder
            {
                $this->queryByConditionsCalled = true;
                // Should never be reached in short-circuit tests; return a noop builder for safety.
                $connection = Mockery::mock(ConnectionInterface::class);
                return new Builder($connection, Mockery::mock(Grammar::class), new Processor());
            }
        };
    }

    /**
     * Returns a DBStorage that delegates scalar execution to the provided fake builder.
     */
    private function makeStorageWithFakeBuilder(FakeConditionsScalarBuilder $builder): DBStorage
    {
        $modelMap = Mockery::mock(IModelMap::class);

        return new class($modelMap, $builder) extends DBStorage {
            private FakeConditionsScalarBuilder $fakeBuilder;

            public function __construct(IModelMap $modelMap, FakeConditionsScalarBuilder $builder)
            {
                parent::__construct($modelMap);
                $this->fakeBuilder = $builder;
            }

            public function queryByConditions(array $conditions, array $columnNames = []): Builder
            {
                return $this->fakeBuilder;
            }
        };
    }

    /**
     * Searches $wheres for a clause of the given type.
     */
    private function findWhere(array $wheres, string $type): ?array
    {
        foreach ($wheres as $where) {
            if (isset($where['type']) && strtolower($where['type']) === strtolower($type)) {
                return $where;
            }
        }

        return null;
    }
}

final class FakeConditionsScalarBuilder extends Builder
{
    /** @var array<string, mixed> */
    private array $results;

    public function __construct(array $results = [])
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        parent::__construct($connection, Mockery::mock(Grammar::class), new Processor());
        $this->results = $results;
    }

    public function count($columns = '*'): mixed
    {
        return $this->results['count'] ?? 0;
    }

    public function exists(): bool
    {
        return (bool)($this->results['exists'] ?? false);
    }

    public function sum($column): mixed
    {
        return $this->results['sum'] ?? 0;
    }

    public function avg($column): mixed
    {
        return $this->results['avg'] ?? null;
    }

    public function min($column): mixed
    {
        return $this->results['min'] ?? null;
    }

    public function max($column): mixed
    {
        return $this->results['max'] ?? null;
    }

    public function value($column): mixed
    {
        return $this->results['value'] ?? null;
    }
}
