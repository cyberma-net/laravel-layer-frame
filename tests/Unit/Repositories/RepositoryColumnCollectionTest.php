<?php

namespace Cyberma\LayerFrame\Tests\Unit\Repositories;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\DBStorage\IDBStorage;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModelFactory;
use Cyberma\LayerFrame\DBStorage\Collections\CollectionQuery;
use Cyberma\LayerFrame\DBStorage\Columns\ColumnQuery;
use Cyberma\LayerFrame\Repositories\Repository;
use Mockery;
use PHPUnit\Framework\TestCase;

class RepositoryColumnCollectionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_column_collection_maps_attributes_and_delegates_to_storage(): void
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
            ->with('name')
            ->andReturn('full_name');

        $dbMapper->shouldReceive('mapAttributeNameToColumn')
            ->once()
            ->with('id')
            ->andReturn('user_id');

        $dbStorage->shouldReceive('collection')
            ->once()
            ->with(
                Mockery::on(function ($query) {
                    return $query instanceof CollectionQuery
                        && $query->valueColumn === 'full_name'
                        && $query->keyColumn === 'user_id'
                        && $query->distinct === true;
                }),
                $mappedConditions
            )
            ->andReturn([1 => 'John', 2 => 'Jane']);

        $repository = new Repository($dbStorage, $dbMapper, $modelMap, $modelFactory);

        $result = $repository->columnCollection(ColumnQuery::pluck('name', 'id', true), $conditions);

        $this->assertSame([1 => 'John', 2 => 'Jane'], $result);
    }
}
