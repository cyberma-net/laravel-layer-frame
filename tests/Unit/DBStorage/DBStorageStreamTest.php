<?php

namespace Cyberma\LayerFrame\Tests\Unit\DBStorage;

use Cyberma\LayerFrame\Contracts\ModelMaps\IModelMap;
use Cyberma\LayerFrame\DBStorage\DBStorage;
use Cyberma\LayerFrame\DBStorage\Streams\StreamQuery;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PHPUnit\Framework\TestCase;

class DBStorageStreamTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_stream_chunk_lazy_and_cursor(): void
    {
        $builder = new FakeStreamBuilder();
        $storage = $this->makeStorage($builder);

        $chunkRows = iterator_to_array($storage->stream(StreamQuery::chunk(2)));
        $lazyRows = iterator_to_array($storage->stream(StreamQuery::lazy(3)));
        $cursorRows = iterator_to_array($storage->stream(StreamQuery::cursor()));

        $this->assertCount(2, $chunkRows);
        $this->assertCount(2, $lazyRows);
        $this->assertCount(2, $cursorRows);
    }

    public function test_stream_chunk_by_id_uses_target_id_column(): void
    {
        $builder = new FakeStreamBuilder();
        $storage = $this->makeStorage($builder);

        $rows = iterator_to_array($storage->stream(StreamQuery::chunkById(10, 'id')));

        $this->assertCount(2, $rows);
        $this->assertSame('id', $builder->tracker->lastLazyByIdColumn);
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

final class FakeStreamBuilder extends Builder
{
    public \stdClass $tracker;

    public function __construct()
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        parent::__construct($connection, new Grammar(), new Processor());
        $this->tracker = (object)['lastLazyByIdColumn' => null];
    }

    public function lazy($chunkSize = 1000)
    {
        yield (object)['id' => 1];
        yield (object)['id' => 2];
    }

    public function lazyById($chunkSize = 1000, $column = 'id', $alias = null)
    {
        $this->tracker->lastLazyByIdColumn = $column;
        yield (object)['id' => 1];
        yield (object)['id' => 2];
    }

    public function cursor()
    {
        yield (object)['id' => 1];
        yield (object)['id' => 2];
    }
}
