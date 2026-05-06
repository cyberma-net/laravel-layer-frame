<?php

namespace Cyberma\LayerFrame\Tests\Unit\DBStorage;

use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\DBStorage\DBStorage;
use Cyberma\LayerFrame\DBStorage\Mutations\MutationQuery;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PHPUnit\Framework\TestCase;

class DBStorageMutationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_mutation_update_delete_increment_decrement(): void
    {
        $builder = new FakeMutationBuilder();
        $storage = $this->makeStorage($builder, false);
        $conditions = [['status', '=', 'active']];

        $this->assertSame(2, $storage->mutate(MutationQuery::update(['name' => 'John']), $conditions));
        $this->assertSame(3, $storage->mutate(MutationQuery::delete(10, true), $conditions));
        $this->assertSame(1, $storage->mutate(MutationQuery::increment('score', 2), $conditions));
        $this->assertSame(1, $storage->mutate(MutationQuery::decrement('score', 2), $conditions));
    }

    public function test_mutation_soft_delete_path_and_empty_conditions_safety(): void
    {
        $builder = new FakeMutationBuilder();
        $storage = $this->makeStorage($builder, true);

        $affectedSoftDelete = $storage->mutate(MutationQuery::delete(5, false), [['status', '=', 'active']]);
        $affectedEmptyUpdate = $storage->mutate(MutationQuery::update(['name' => 'John']), []);

        $this->assertSame(9, $affectedSoftDelete);
        $this->assertSame(0, $affectedEmptyUpdate);
        $this->assertTrue($builder->tracker->softDeleteTriggered);
    }

    private function makeStorage(Builder $builder, bool $softDeletes): DBStorage
    {
        /** @var IModelMap $modelMap */
        $modelMap = Mockery::mock(IModelMap::class);
        $modelMap->shouldReceive('hasSoftDeletes')->andReturn($softDeletes);

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

final class FakeMutationBuilder extends Builder
{
    public \stdClass $tracker;

    public function __construct()
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        parent::__construct($connection, Mockery::mock(Grammar::class), new Processor());
        $this->tracker = (object)['softDeleteTriggered' => false];
    }

    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $this->tracker->softDeleteTriggered = true;
        return $this;
    }

    public function limit($value)
    {
        return $this;
    }

    public function update(array $values)
    {
        if (array_key_exists('deleted_at', $values)) {
            return 9;
        }

        return 2;
    }

    public function delete($id = null)
    {
        return 3;
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        return 1;
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        return 1;
    }
}
