<?php

namespace Cyberma\LayerFrame\Tests\Unit\DBStorage\Aggregates;

use Cyberma\LayerFrame\DBStorage\Aggregates\AggregateType;
use Cyberma\LayerFrame\DBStorage\Aggregates\ScalarQuery;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ScalarQueryTest extends TestCase
{
    public function test_factory_methods_create_expected_queries(): void
    {
        $count = ScalarQuery::count();
        $countDistinct = ScalarQuery::count('userId', null, true);
        $sum = ScalarQuery::sum('price');
        $avg = ScalarQuery::avg('score');
        $min = ScalarQuery::min('price', 'min_price', true);
        $max = ScalarQuery::max('price');
        $value = ScalarQuery::value('email');
        $exists = ScalarQuery::exists();

        $this->assertSame(AggregateType::COUNT, $count->type);
        $this->assertNull($count->column);
        $this->assertSame('userId', $countDistinct->column);
        $this->assertTrue($countDistinct->distinct);
        $this->assertSame(AggregateType::SUM, $sum->type);
        $this->assertSame('price', $sum->column);
        $this->assertSame(AggregateType::AVG, $avg->type);
        $this->assertSame('score', $avg->column);
        $this->assertSame(AggregateType::MIN, $min->type);
        $this->assertSame('min_price', $min->alias);
        $this->assertTrue($min->distinct);
        $this->assertSame(AggregateType::MAX, $max->type);
        $this->assertSame(AggregateType::VALUE, $value->type);
        $this->assertSame('email', $value->column);
        $this->assertSame(AggregateType::EXISTS, $exists->type);
        $this->assertNull($exists->column);
    }

    public function test_it_validates_column_requirement_based_on_aggregate_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a target column');

        ScalarQuery::make(AggregateType::SUM);
    }

    public function test_it_rejects_column_for_non_column_aggregate_types(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not support a target column');

        ScalarQuery::make(AggregateType::EXISTS, 'id');
    }

    public function test_it_rejects_empty_column_or_alias(): void
    {
        try {
            ScalarQuery::sum(' ');
            $this->fail('Expected invalid empty column exception.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('column cannot be empty', $e->getMessage());
        }

        try {
            ScalarQuery::count(null, ' ');
            $this->fail('Expected invalid empty alias exception.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('alias cannot be empty', $e->getMessage());
        }
    }

    public function test_it_is_immutable(): void
    {
        $query = ScalarQuery::value('email');

        $this->expectException(\Error::class);
        $query->column = 'username';
    }
}
