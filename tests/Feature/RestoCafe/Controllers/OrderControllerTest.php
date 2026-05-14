<?php

namespace Tests\Feature\RestoCafe\Controllers;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Tables\Models\RestaurantTable;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class OrderControllerTest extends RestoCafeTestCase
{
    public function test_index_renders_orders_for_admin(): void
    {
        $this->makeOrder($this->waiter());
        $this->actingAs($this->admin())
            ->get(route('orders.index'))
            ->assertOk();
    }

    public function test_waiter_only_sees_own_orders(): void
    {
        $waiter = $this->waiter();
        $manager = $this->manager();
        $tables = $this->availableTables(2);
        $a = $this->makeOrder($waiter, $tables[0]);
        $b = $this->makeOrder($manager, $tables[1]);

        $resp = $this->actingAs($waiter)->get(route('orders.index'))->assertOk();
        $html = $resp->content();
        $this->assertStringContainsString('&quot;id&quot;:'.$a->id.',', $html);
        $this->assertStringNotContainsString('&quot;id&quot;:'.$b->id.',', $html);
    }

    public function test_cashier_sees_only_ready_and_served(): void
    {
        $waiter = $this->waiter();
        $cashier = $this->cashier();
        $tables = $this->availableTables(3);
        $new = $this->makeOrder($waiter, $tables[0]);
        $ready = $this->makeOrder($waiter, $tables[1]);
        $ready->update(['status' => OrderStatus::Ready]);
        $served = $this->makeOrder($waiter, $tables[2]);
        $served->update(['status' => OrderStatus::Served]);

        $resp = $this->actingAs($cashier)->get(route('orders.index'))->assertOk();
        $html = $resp->content();
        $this->assertStringNotContainsString('&quot;id&quot;:'.$new->id.',', $html);
        $this->assertStringContainsString('&quot;id&quot;:'.$ready->id.',', $html);
        $this->assertStringContainsString('&quot;id&quot;:'.$served->id.',', $html);
    }

    public function test_kitchen_cannot_access_orders_index(): void
    {
        $this->actingAs($this->kitchen())
            ->get(route('orders.index'))
            ->assertForbidden();
    }

    public function test_create_shows_available_tables(): void
    {
        $this->actingAs($this->waiter())
            ->get(route('orders.create'))
            ->assertOk();
    }

    public function test_create_with_specific_available_table_ok(): void
    {
        $table = $this->firstTable();
        $this->actingAs($this->waiter())
            ->get(route('orders.create', ['table_id' => $table->id]))
            ->assertOk();
    }

    public function test_create_with_unavailable_table_404s(): void
    {
        $table = $this->firstTable();
        $table->update(['status' => TableStatus::Occupied]);
        $this->actingAs($this->waiter())
            ->get(route('orders.create', ['table_id' => $table->id]))
            ->assertNotFound();
    }

    public function test_create_with_cross_branch_table_404s(): void
    {
        $other = $this->makeSecondaryBranch();
        $this->actingAs($this->waiter())
            ->get(route('orders.create', ['table_id' => $other['table']->id]))
            ->assertNotFound();
    }

    public function test_store_creates_order(): void
    {
        $mi = $this->menuItems(1)[0];
        $table = $this->firstTable();

        $this->actingAs($this->waiter())->post(route('orders.store'), [
            'table_id' => $table->id,
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertSame(1, Order::query()->count());
    }

    public function test_store_validates_missing_items(): void
    {
        $table = $this->firstTable();
        $this->actingAs($this->waiter())->post(route('orders.store'), [
            'table_id' => $table->id,
            'items' => [],
        ])->assertSessionHasErrors(['items']);
    }

    public function test_store_validates_menu_item_exists(): void
    {
        $table = $this->firstTable();
        $this->actingAs($this->waiter())->post(route('orders.store'), [
            'table_id' => $table->id,
            'items' => [['menu_item_id' => 999999, 'quantity' => 1]],
        ])->assertSessionHasErrors(['items.0.menu_item_id']);
    }

    public function test_store_validates_quantity_min(): void
    {
        $table = $this->firstTable();
        $mi = $this->menuItems(1)[0];
        $this->actingAs($this->waiter())->post(route('orders.store'), [
            'table_id' => $table->id,
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 0]],
        ])->assertSessionHasErrors(['items.0.quantity']);
    }

    public function test_store_validates_quantity_max(): void
    {
        $table = $this->firstTable();
        $mi = $this->menuItems(1)[0];
        $this->actingAs($this->waiter())->post(route('orders.store'), [
            'table_id' => $table->id,
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 100]],
        ])->assertSessionHasErrors(['items.0.quantity']);
    }

    public function test_store_forbidden_for_kitchen_role(): void
    {
        $table = $this->firstTable();
        $mi = $this->menuItems(1)[0];
        $this->actingAs($this->kitchen())->post(route('orders.store'), [
            'table_id' => $table->id,
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 1]],
        ])->assertForbidden();
    }

    public function test_show_branch_access_blocks_cross_branch(): void
    {
        $other = $this->makeSecondaryBranch();
        $orderForeign = $this->makeOrder($other['users']['waiter'], $other['table']);
        $this->actingAs($this->admin())
            ->get(route('orders.show', $orderForeign))
            ->assertNotFound();
    }

    public function test_show_waiter_cannot_see_other_waiters_order(): void
    {
        $mgr = $this->manager();
        $other = $this->makeOrder($mgr);
        $this->actingAs($this->waiter())
            ->get(route('orders.show', $other))
            ->assertForbidden();
    }

    public function test_show_cashier_can_see_any_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $this->actingAs($this->cashier())
            ->get(route('orders.show', $order))
            ->assertOk();
    }

    public function test_show_manager_can_see_any_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $this->actingAs($this->manager())
            ->get(route('orders.show', $order))
            ->assertOk();
    }

    public function test_add_items_success(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $mi = $this->menuItems(1)[0];

        $this->actingAs($waiter)->post(route('orders.items.store', $order), [
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 1]],
        ])->assertRedirect(route('orders.show', $order));
    }

    public function test_add_items_forbidden_for_other_waiter(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);

        $otherWaiter = \App\Models\User::query()->create([
            'branch_id' => 1,
            'name' => 'W2',
            'email' => 'w2@rc.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true,
        ]);
        $otherWaiter->syncRoles(['waiter']);

        $mi = $this->menuItems(1)[0];
        $this->actingAs($otherWaiter)->post(route('orders.items.store', $order), [
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 1]],
        ])->assertForbidden();
    }

    public function test_add_items_catches_runtime_error_on_non_new_order(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $order->update(['status' => OrderStatus::InKitchen]);
        $mi = $this->menuItems(1)[0];

        $this->actingAs($waiter)->post(route('orders.items.store', $order), [
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 1]],
        ])->assertRedirect()->assertSessionHas('error');
    }

    public function test_submit_success(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);

        $this->actingAs($waiter)->patch(route('orders.submit', $order))
            ->assertRedirect(route('orders.show', $order));
        $this->assertSame(OrderStatus::InKitchen, $order->fresh()->status);
    }

    public function test_submit_error_flash_on_bad_state(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $order->update(['status' => OrderStatus::InKitchen]);

        $this->actingAs($waiter)->patch(route('orders.submit', $order))
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_cancel_new_order_by_owner_waiter(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);

        $this->actingAs($waiter)->patch(route('orders.cancel', $order), [
            'reason' => 'nope',
        ])->assertRedirect(route('orders.index'));
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_cancel_waiter_cannot_cancel_in_kitchen(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $order->update(['status' => OrderStatus::InKitchen]);

        $this->actingAs($waiter)->patch(route('orders.cancel', $order), [
            'reason' => 'too late',
        ])->assertForbidden();
    }

    public function test_cancel_manager_can_cancel_in_kitchen(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::InKitchen]);

        $this->actingAs($this->manager())
            ->patch(route('orders.cancel', $order), ['reason' => 'ok'])
            ->assertRedirect(route('orders.index'));
    }

    public function test_cancel_runtime_error_when_ready(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);

        $this->actingAs($this->manager())
            ->patch(route('orders.cancel', $order), ['reason' => 'x'])
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_unauthenticated_redirected_from_index(): void
    {
        $this->get(route('orders.index'))->assertRedirect(route('login'));
    }
}
