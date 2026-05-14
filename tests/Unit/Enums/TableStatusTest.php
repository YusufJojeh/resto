<?php

namespace Tests\Unit\Enums;

use App\Enums\TableStatus;
use PHPUnit\Framework\TestCase;

class TableStatusTest extends TestCase
{
    public function test_enum_values(): void
    {
        $values = array_map(fn ($c) => $c->value, TableStatus::cases());
        sort($values);
        $this->assertSame(['available', 'occupied', 'reserved'], $values);
    }

    public function test_individual_mappings(): void
    {
        $this->assertSame('available', TableStatus::Available->value);
        $this->assertSame('occupied', TableStatus::Occupied->value);
        $this->assertSame('reserved', TableStatus::Reserved->value);
    }
}
