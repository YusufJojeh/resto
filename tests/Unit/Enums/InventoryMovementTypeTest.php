<?php

namespace Tests\Unit\Enums;

use App\Enums\InventoryMovementType;
use PHPUnit\Framework\TestCase;

class InventoryMovementTypeTest extends TestCase
{
    public function test_all_cases_exposed(): void
    {
        $values = array_map(fn ($c) => $c->value, InventoryMovementType::cases());
        sort($values);
        $this->assertSame(['correction', 'deduction', 'restock', 'waste'], $values);
    }

    public function test_from_values(): void
    {
        $this->assertSame(InventoryMovementType::Restock, InventoryMovementType::from('restock'));
        $this->assertSame(InventoryMovementType::Deduction, InventoryMovementType::from('deduction'));
        $this->assertSame(InventoryMovementType::Waste, InventoryMovementType::from('waste'));
        $this->assertSame(InventoryMovementType::Correction, InventoryMovementType::from('correction'));
    }
}
