<?php

namespace Tests\Feature\RestoCafe\Controllers;

use App\Modules\Inventory\Models\InventoryItem;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class InventoryControllerTest extends RestoCafeTestCase
{
    protected function item(): InventoryItem
    {
        return InventoryItem::query()->where('branch_id', 1)->first();
    }

    public function test_index_ok_for_manager(): void
    {
        $this->actingAs($this->manager())->get(route('inventory.index'))->assertOk();
    }

    public function test_index_forbidden_for_waiter(): void
    {
        $this->actingAs($this->waiter())->get(route('inventory.index'))->assertForbidden();
    }

    public function test_create_page_ok(): void
    {
        $this->actingAs($this->manager())->get(route('inventory.create'))->assertOk();
    }

    public function test_store_creates_inventory_item(): void
    {
        $this->actingAs($this->manager())->post(route('inventory.store'), [
            'name' => 'Milk',
            'unit' => 'L',
            'quantity' => 5,
            'low_threshold' => 1,
        ])->assertRedirect(route('inventory.index'));
        $this->assertDatabaseHas('inventory_items', ['name' => 'Milk', 'branch_id' => 1]);
    }

    public function test_store_validation_errors(): void
    {
        $this->actingAs($this->manager())->post(route('inventory.store'), [])
            ->assertSessionHasErrors(['name', 'unit', 'quantity', 'low_threshold']);
    }

    public function test_store_rejects_negative_quantity(): void
    {
        $this->actingAs($this->manager())->post(route('inventory.store'), [
            'name' => 'x', 'unit' => 'kg', 'quantity' => -1, 'low_threshold' => 0,
        ])->assertSessionHasErrors(['quantity']);
    }

    public function test_store_rejects_cross_branch_menu_item(): void
    {
        $other = $this->makeSecondaryBranch();
        $this->actingAs($this->manager())->post(route('inventory.store'), [
            'menu_item_id' => $other['menuItem']->id + 999,
            'name' => 'x', 'unit' => 'kg', 'quantity' => 1, 'low_threshold' => 0,
        ])->assertSessionHasErrors(['menu_item_id']);
    }

    public function test_edit_ok(): void
    {
        $this->actingAs($this->manager())
            ->get(route('inventory.edit', $this->item()))->assertOk();
    }

    public function test_edit_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $this->actingAs($this->manager())
            ->get(route('inventory.edit', $other['inventory']))
            ->assertNotFound();
    }

    public function test_update_ok(): void
    {
        $item = $this->item();
        $this->actingAs($this->manager())
            ->put(route('inventory.update', $item), [
                'name' => 'Updated', 'unit' => $item->unit,
                'quantity' => $item->quantity, 'low_threshold' => $item->low_threshold,
            ])
            ->assertRedirect(route('inventory.index'));
        $this->assertSame('Updated', $item->fresh()->name);
    }

    public function test_adjust_success(): void
    {
        $item = $this->item();
        $this->actingAs($this->manager())
            ->post(route('inventory.adjust', $item), [
                'adjustment' => 5, 'reason' => 'restock',
            ])
            ->assertRedirect(route('inventory.index'));
    }

    public function test_adjust_validation_zero(): void
    {
        $item = $this->item();
        $this->actingAs($this->manager())
            ->post(route('inventory.adjust', $item), [
                'adjustment' => 0, 'reason' => 'noop',
            ])
            ->assertSessionHasErrors(['adjustment']);
    }

    public function test_adjust_validation_missing_reason(): void
    {
        $item = $this->item();
        $this->actingAs($this->manager())
            ->post(route('inventory.adjust', $item), [
                'adjustment' => 1,
            ])
            ->assertSessionHasErrors(['reason']);
    }

    public function test_adjust_runtime_error_flashes(): void
    {
        $item = $this->item();
        $this->actingAs($this->manager())
            ->post(route('inventory.adjust', $item), [
                'adjustment' => -99999, 'reason' => 'too much',
            ])
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_adjust_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $this->actingAs($this->manager())
            ->post(route('inventory.adjust', $other['inventory']), [
                'adjustment' => 1, 'reason' => 'x',
            ])
            ->assertNotFound();
    }

    public function test_adjust_forbidden_for_waiter(): void
    {
        $item = $this->item();
        $this->actingAs($this->waiter())
            ->post(route('inventory.adjust', $item), [
                'adjustment' => 1, 'reason' => 'x',
            ])
            ->assertForbidden();
    }

    public function test_update_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $this->actingAs($this->manager())
            ->put(route('inventory.update', $other['inventory']), [
                'name' => 'x', 'unit' => 'kg', 'quantity' => 1, 'low_threshold' => 0,
            ])
            ->assertNotFound();
    }
}
