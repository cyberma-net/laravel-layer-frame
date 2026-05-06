<?php

namespace Cyberma\LayerFrame\Tests\Unit\DBStorage\Columns;

use Cyberma\LayerFrame\DBStorage\Columns\ColumnQuery;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ColumnQueryTest extends TestCase
{
    public function test_it_creates_flat_pluck_query(): void
    {
        $query = ColumnQuery::pluck('id');

        $this->assertSame('id', $query->valueColumn);
        $this->assertNull($query->keyColumn);
        $this->assertFalse($query->distinct);
        $this->assertFalse($query->isKeyValue());
    }

    public function test_it_creates_key_value_pluck_query(): void
    {
        $query = ColumnQuery::pluck('name', 'id', true);

        $this->assertSame('name', $query->valueColumn);
        $this->assertSame('id', $query->keyColumn);
        $this->assertTrue($query->distinct);
        $this->assertTrue($query->isKeyValue());
    }

    public function test_it_rejects_invalid_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('value column cannot be empty');
        ColumnQuery::pluck(' ');
    }

    public function test_it_rejects_same_key_and_value_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be different');
        ColumnQuery::pluck('id', 'id');
    }
}
