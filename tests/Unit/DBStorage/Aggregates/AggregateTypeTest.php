<?php

namespace Cyberma\LayerFrame\Tests\Unit\DBStorage\Aggregates;

use Cyberma\LayerFrame\DBStorage\Aggregates\AggregateType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AggregateTypeTest extends TestCase
{
    public function test_it_creates_aggregate_type_from_valid_string_case_insensitive(): void
    {
        $this->assertSame(AggregateType::COUNT, AggregateType::fromString('count'));
        $this->assertSame(AggregateType::SUM, AggregateType::fromString(' SUM '));
        $this->assertSame(AggregateType::AVG, AggregateType::fromString('AvG'));
    }

    public function test_it_throws_for_unsupported_aggregate_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported aggregate type "median"');

        AggregateType::fromString('median');
    }

    public function test_it_exposes_only_supported_values(): void
    {
        $this->assertSame(
            ['count', 'sum', 'avg', 'min', 'max', 'value', 'exists'],
            AggregateType::values()
        );
    }

    public function test_it_knows_if_column_is_required(): void
    {
        $this->assertFalse(AggregateType::COUNT->requiresColumn());
        $this->assertFalse(AggregateType::EXISTS->requiresColumn());
        $this->assertTrue(AggregateType::VALUE->requiresColumn());
        $this->assertTrue(AggregateType::MAX->requiresColumn());
        $this->assertTrue(AggregateType::COUNT->supportsColumn());
        $this->assertFalse(AggregateType::EXISTS->supportsColumn());
    }
}
