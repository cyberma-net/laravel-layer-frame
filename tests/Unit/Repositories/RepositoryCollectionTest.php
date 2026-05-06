<?php

namespace Cyberma\LayerFrame\Tests\Unit\Repositories;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\DBStorage\IDBStorage;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModelFactory;
use Cyberma\LayerFrame\DBStorage\Collections\CollectionQuery;
use Cyberma\LayerFrame\Repositories\Repository;
use Mockery;
use PHPUnit\Framework\TestCase;

class RepositoryCollectionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_collection_maps_columns_and_delegates(): void
    {
        $dbStorage = Mockery::mock(IDBStorage::class);
        $dbMapper = Mockery::mock(IDBMapper::class);
        $modelMap = Mockery::mock(IModelMap::class);
        $modelFactory = Mockery::mock(IModelFactory::class);

        $conditions = [['status', '=', 'active']];
        $mappedConditions = [['status_col', '=', 'active']];

        $dbMapper->shouldReceive('mapConditionsColumnNames')->once()->with($conditions)->andReturn($mappedConditions);
        $dbMapper->shouldReceive('mapAttributeNameToColumn')->once()->with('id')->andReturn('id_col');

        $dbStorage->shouldReceive('collection')
            ->once()
            ->with(Mockery::on(fn($q) => $q instanceof CollectionQuery && $q->valueColumn === 'id_col'), $mappedConditions)
            ->andReturn([1, 2]);

        $repository = new Repository($dbStorage, $dbMapper, $modelMap, $modelFactory);
        $result = $repository->collection(CollectionQuery::ids(), $conditions);

        $this->assertSame([1, 2], $result);
    }
}
