<?php

namespace Tests\Feature\RestoCafe\Controllers;

use App\Enums\TableStatus;
use App\Modules\Tables\Models\RestaurantTable;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class TableControllerTest extends RestoCafeTestCase
{
    public function test_index_visible_to_manager_waiter_cashier_admin(): void
    {
        foreach ([$this->admin(), $this->manager(), $this->waiter(), $this->cashier()] as $u) {
            $this->actingAs($u)->get(route('tables.index'))->assertOk();
        }
    }

    public function test_index_forbidden_for_kitchen(): void
    {
        $this->actingAs($this->kitchen())->get(route('tables.index'))->assertForbidden();
    }

    public function test_create_form_ok(): void
    {
        $this->actingAs($this->manager())->get(route('tables.create'))->assertOk();
    }

    public function test_create_forbidden_for_waiter(): void
    {
        $this->actingAs($this->waiter())->get(route('tables.create'))->assertForbidden();
    }

    public function test_store_creates(): void
    {
        $this->actingAs($this->manager())->post(route('tables.store'), [
            'number' => 99,
            'name' => 'VIP',
            'capacity' => 10,
        ])->assertRedirect(route('tables.index'));
        $this->assertDatabaseHas('restaurant_tables', ['number' => 99, 'branch_id' => 1]);
    }

    public function test_store_unique_number_per_branch(): void
    {
        $this->actingAs($this->manager())->post(route('tables.store'), [
            'number' => 1, // already exists
            'capacity' => 4,
        ])->assertSessionHasErrors(['number']);
    }

    public function test_store_validation_errors(): void
    {
        $this->actingAs($this->manager())->post(route('tables.store'), [])
            ->assertSessionHasErrors(['number', 'capacity']);
    }

    public function test_store_rejects_capacity_over_50(): void
    {
        $this->actingAs($this->manager())->post(route('tables.store'), [
            'number' => 100, 'capacity' => 51,
        ])->assertSessionHasErrors(['capacity']);
    }

    public function test_edit_and_update(): void
    {
        $t = $this->firstTable();
        $this->actingAs($this->manager())->get(route('tables.edit', $t))->assertOk();

        $this->actingAs($this->manager())->put(route('tables.update', $t), [
            'number' => $t->number,
            'name' => 'Renamed',
            'capacity' => 8,
        ])->assertRedirect(route('tables.index'));
        $this->assertSame('Renamed', $t->fresh()->name);
        $this->assertSame(8, $t->fresh()->capacity);
    }

    public function test_update_preserves_status_due_to_except(): void
    {
        $t = $this->firstTable();
        $t->update(['status' => TableStatus::Occupied]);
        $this->actingAs($this->manager())->put(route('tables.update', $t), [
            'number' => $t->number,
            'capacity' => $t->capacity,
            'status' => 'available',
        ])->assertRedirect();

        $this->assertSame(TableStatus::Occupied, $t->fresh()->status);
    }

    public function test_update_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $this->actingAs($this->manager())->put(route('tables.update', $other['table']), [
            'number' => 99, 'capacity' => 4,
        ])->assertNotFound();
    }

    public function test_status_change_available_to_reserved(): void
    {
        $t = $this->firstTable();
        $this->actingAs($this->manager())->patch(route('tables.status', $t), [
            'status' => 'reserved',
        ])->assertRedirect();
        $this->assertSame(TableStatus::Reserved, $t->fresh()->status);
    }

    public function test_status_change_forbidden_for_waiter(): void
    {
        $t = $this->firstTable();
        $this->actingAs($this->waiter())->patch(route('tables.status', $t), [
            'status' => 'reserved',
        ])->assertForbidden();
    }

    public function test_status_invalid_value_validation(): void
    {
        $t = $this->firstTable();
        $this->actingAs($this->manager())->patch(route('tables.status', $t), [
            'status' => 'occupied',
        ])->assertSessionHasErrors(['status']);
    }

    public function test_status_missing_validation(): void
    {
        $t = $this->firstTable();
        $this->actingAs($this->manager())->patch(route('tables.status', $t), [])
            ->assertSessionHasErrors(['status']);
    }

    public function test_status_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $this->actingAs($this->manager())->patch(route('tables.status', $other['table']), [
            'status' => 'reserved',
        ])->assertNotFound();
    }

    public function test_edit_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $this->actingAs($this->manager())->get(route('tables.edit', $other['table']))->assertNotFound();
    }
}
