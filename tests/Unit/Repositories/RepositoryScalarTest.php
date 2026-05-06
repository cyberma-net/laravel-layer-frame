<?php

namespace Cyberma\LayerFrame\Tests\Unit\Repositories;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\DBStorage\IDBStorage;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModelFactory;
use Cyberma\LayerFrame\DBStorage\Aggregates\AggregateType;
use Cyberma\LayerFrame\DBStorage\Aggregates\ScalarQuery;
use Cyberma\LayerFrame\Repositories\Repository;
use Mockery;
use PHPUnit\Framework\TestCase;

class RepositoryScalarTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_scalar_maps_conditions_and_scalar_column_then_delegates_to_storage(): void
    {
        $dbStorage = Mockery::mock(IDBStorage::class);
        $dbMapper = Mockery::mock(IDBMapper::class);
        $modelMap = Mockery::mock(IModelMap::class);
        $modelFactory = Mockery::mock(IModelFactory::class);

        $conditions = [['status', '=', 'active']];
        $mappedConditions = [['status_col', '=', 'active']];

        $dbMapper->shouldReceive('mapConditionsColumnNames')
            ->once()
            ->with($conditions)
            ->andReturn($mappedConditions);

        $dbMapper->shouldReceive('mapAttributeNameToColumn')
            ->once()
            ->with('price')
            ->andReturn('price_col');

        $dbStorage->shouldReceive('executeScalarByConditions')
            ->once()
            ->with(
                $mappedConditions,
                AggregateType::SUM,
                'price_col',
                ['distinct' => true, 'alias' => 'total_price']
            )
            ->andReturn(120.5);

        $repository = new Repository($dbStorage, $dbMapper, $modelMap, $modelFactory);

        $result = $repository->scalar(ScalarQuery::sum('price', 'total_price', true), $conditions);

        $this->assertSame(120.5, $result);
    }

    public function test_scalar_without_target_column_does_not_map_column(): void
    {
        $dbStorage = Mockery::mock(IDBStorage::class);
        $dbMapper = Mockery::mock(IDBMapper::class);
        $modelMap = Mockery::mock(IModelMap::class);
        $modelFactory = Mockery::mock(IModelFactory::class);

        $conditions = [['status', '=', 'active']];
        $mappedConditions = [['status_col', '=', 'active']];

        $dbMapper->shouldReceive('mapConditionsColumnNames')
            ->once()
            ->with($conditions)
            ->andReturn($mappedConditions);

        $dbMapper->shouldNotReceive('mapAttributeNameToColumn');

        $dbStorage->shouldReceive('executeScalarByConditions')
            ->once()
            ->with(
                $mappedConditions,
                AggregateType::COUNT,
                null,
                ['distinct' => false, 'alias' => null]
            )
            ->andReturn(7);

        $repository = new Repository($dbStorage, $dbMapper, $modelMap, $modelFactory);

        $result = $repository->scalar(ScalarQuery::count(), $conditions);

        $this->assertSame(7, $result);
    }

    public function test_scalar_count_with_column_maps_column_and_distinct(): void
    {
        $dbStorage = Mockery::mock(IDBStorage::class);
        $dbMapper = Mockery::mock(IDBMapper::class);
        $modelMap = Mockery::mock(IModelMap::class);
        $modelFactory = Mockery::mock(IModelFactory::class);

        $conditions = [['status', '=', 'active']];
        $mappedConditions = [['status_col', '=', 'active']];

        $dbMapper->shouldReceive('mapConditionsColumnNames')->once()->with($conditions)->andReturn($mappedConditions);
        $dbMapper->shouldReceive('mapAttributeNameToColumn')->once()->with('userId')->andReturn('user_id');

        $dbStorage->shouldReceive('executeScalarByConditions')
            ->once()
            ->with(
                $mappedConditions,
                AggregateType::COUNT,
                'user_id',
                ['distinct' => true, 'alias' => null]
            )
            ->andReturn(3);

        $repository = new Repository($dbStorage, $dbMapper, $modelMap, $modelFactory);
        $result = $repository->scalar(ScalarQuery::count('userId', null, true), $conditions);

        $this->assertSame(3, $result);
    }

    public function test_legacy_get_count_is_thin_wrapper(): void
    {
        $dbStorage = Mockery::mock(IDBStorage::class);
        $dbMapper = Mockery::mock(IDBMapper::class);
        $modelMap = Mockery::mock(IModelMap::class);
        $modelFactory = Mockery::mock(IModelFactory::class);

        $conditions = [['status', '=', 'active']];
        $mappedConditions = [['status_col', '=', 'active']];

        $dbMapper->shouldReceive('mapConditionsColumnNames')->once()->with($conditions)->andReturn($mappedConditions);

        $dbStorage->shouldReceive('executeScalarByConditions')->once()
            ->with($mappedConditions, AggregateType::COUNT, null, [])
            ->andReturn(4);

        $repository = new Repository($dbStorage, $dbMapper, $modelMap, $modelFactory);

        $this->assertSame(4, $repository->getCount($conditions));
    }
}
