<?php

namespace Tests\Feature\RestoCafe\Actions;

use App\Enums\InventoryMovementType;
use App\Modules\Inventory\Actions\AdjustStock;
use App\Modules\Inventory\Models\InventoryItem;
use RuntimeException;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class AdjustStockTest extends RestoCafeTestCase
{
    protected function item(): InventoryItem
    {
        return InventoryItem::query()->where('branch_id', 1)->first();
    }

    public function test_positive_adjustment_records_restock(): void
    {
        $manager = $this->manager();
        $item = $this->item();
        $before = (float) $item->quantity;

        $result = app(AdjustStock::class)->handle($manager, $item, 5.5, 'new delivery');

        $this->assertEqualsWithDelta($before + 5.5, (float) $result->quantity, 0.001);
        $movement = $result->stockMovements()->latest('id')->first();
        $this->assertSame(InventoryMovementType::Restock, $movement->type);
        $this->assertEqualsWithDelta($before, (float) $movement->quantity_before, 0.001);
        $this->assertEqualsWithDelta($before + 5.5, (float) $movement->quantity_after, 0.001);
        $this->assertSame('new delivery', $movement->reason);
        $this->assertSame($manager->id, $movement->user_id);
    }

    public function test_negative_adjustment_records_deduction(): void
    {
        $manager = $this->manager();
        $item = $this->item();
        $before = (float) $item->quantity;

        $result = app(AdjustStock::class)->handle($manager, $item, -3.0, 'wasted');

        $this->assertEqualsWithDelta($before - 3.0, (float) $result->quantity, 0.001);
        $this->assertSame(InventoryMovementType::Deduction, $result->stockMovements()->latest('id')->first()->type);
    }

    public function test_rejects_below_zero(): void
    {
        $manager = $this->manager();
        $item = $this->item();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stock cannot go below zero.');
        app(AdjustStock::class)->handle($manager, $item, -9999, 'bad');
    }

    public function test_rollback_on_failure_preserves_quantity(): void
    {
        $manager = $this->manager();
        $item = $this->item();
        $before = (float) $item->quantity;

        try {
            app(AdjustStock::class)->handle($manager, $item, -9999, 'bad');
        } catch (RuntimeException) {
        }

        $this->assertEqualsWithDelta($before, (float) $item->fresh()->quantity, 0.001);
        $this->assertSame(0, $item->stockMovements()->count());
    }

    public function test_allows_exact_zero_result(): void
    {
        $manager = $this->manager();
        $item = $this->item();
        $delta = -((float) $item->quantity);

        $result = app(AdjustStock::class)->handle($manager, $item, $delta, 'zero out');
        $this->assertEqualsWithDelta(0.0, (float) $result->quantity, 0.001);
    }
}
