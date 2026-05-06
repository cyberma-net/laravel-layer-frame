<?php

namespace Cyberma\LayerFrame\Tests\Unit\Repositories;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\DBStorage\IDBStorage;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModelFactory;
use Cyberma\LayerFrame\DBStorage\Mutations\MutationQuery;
use Cyberma\LayerFrame\Repositories\Repository;
use Mockery;
use PHPUnit\Framework\TestCase;

class RepositoryMutationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_mutate_maps_conditions_columns_and_values(): void
    {
        $dbStorage = Mockery::mock(IDBStorage::class);
        $dbMapper = Mockery::mock(IDBMapper::class);
        $modelMap = Mockery::mock(IModelMap::class);
        $modelFactory = Mockery::mock(IModelFactory::class);

        $conditions = [['status', '=', 'active']];
        $mappedConditions = [['status_col', '=', 'active']];

        $dbMapper->shouldReceive('mapConditionsColumnNames')->once()->andReturn($mappedConditions);
        $dbMapper->shouldReceive('mapAttributesToColumns')->once()->with(['name' => 'John'])->andReturn(['full_name' => 'John']);

        $dbStorage->shouldReceive('mutate')
            ->once()
            ->with(
                Mockery::on(fn($q) => $q instanceof MutationQuery && $q->values === ['full_name' => 'John']),
                $mappedConditions
            )
            ->andReturn(2);

        $repository = new Repository($dbStorage, $dbMapper, $modelMap, $modelFactory);
        $affected = $repository->mutate(MutationQuery::update(['name' => 'John']), $conditions);

        $this->assertSame(2, $affected);
    }
}
