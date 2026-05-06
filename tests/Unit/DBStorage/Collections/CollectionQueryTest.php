<?php

namespace Cyberma\LayerFrame\Tests\Unit\DBStorage\Collections;

use Cyberma\LayerFrame\DBStorage\Collections\CollectionQuery;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CollectionQueryTest extends TestCase
{
    public function test_it_creates_flat_and_key_value_queries(): void
    {
        $flat = CollectionQuery::pluck('id');
        $keyValue = CollectionQuery::pluck('name', 'id', true);
        $distinct = CollectionQuery::distinctColumn('status');
        $ids = CollectionQuery::ids();

        $this->assertSame('id', $flat->valueColumn);
        $this->assertNull($flat->keyColumn);
        $this->assertFalse($flat->distinct);
        $this->assertSame('name', $keyValue->valueColumn);
        $this->assertSame('id', $keyValue->keyColumn);
        $this->assertTrue($keyValue->distinct);
        $this->assertSame('status', $distinct->valueColumn);
        $this->assertTrue($distinct->distinct);
        $this->assertSame('id', $ids->valueColumn);
    }

    public function test_it_rejects_invalid_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CollectionQuery::pluck(' ', 'id');
    }
}
