<?php

namespace Cyberma\LayerFrame\Tests\Unit\Repositories;

use Cyberma\LayerFrame\Contracts\DBMappers\IDBMapper;
use Cyberma\LayerFrame\Contracts\DBStorage\IDBStorage;
use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\Contracts\Models\IModelFactory;
use Cyberma\LayerFrame\DBStorage\Streams\StreamQuery;
use Cyberma\LayerFrame\Repositories\Repository;
use Mockery;
use PHPUnit\Framework\TestCase;

class RepositoryStreamTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_stream_maps_attributes_and_delegates(): void
    {
        $dbStorage = Mockery::mock(IDBStorage::class);
        $dbMapper = Mockery::mock(IDBMapper::class);
        $modelMap = Mockery::mock(IModelMap::class);
        $modelFactory = Mockery::mock(IModelFactory::class);

        $conditions = [['status', '=', 'active']];
        $mappedConditions = [['status_col', '=', 'active']];

        $dbMapper->shouldReceive('mapConditionsColumnNames')->once()->with($conditions)->andReturn($mappedConditions);
        $dbMapper->shouldReceive('mapAttributesNamesToColumns')->once()->with(['id', 'email'])->andReturn(['id_col', 'email_col']);
        $dbMapper->shouldReceive('mapAttributeNameToColumn')->once()->with('id')->andReturn('id_col');

        $dbStorage->shouldReceive('stream')
            ->once()
            ->with(
                Mockery::on(fn($q) => $q instanceof StreamQuery && $q->idColumn === 'id_col' && $q->columns === ['id_col', 'email_col']),
                $mappedConditions
            )
            ->andReturn((function () {
                yield (object)['id_col' => 1, 'email_col' => 'john@example.com'];
            })());

        $repository = new Repository($dbStorage, $dbMapper, $modelMap, $modelFactory);
        $result = iterator_to_array($repository->stream(StreamQuery::chunkById(100, 'id', ['id', 'email']), $conditions));

        $this->assertCount(1, $result);
    }
}
