<?php

namespace Tests\Feature\RestoCafe\Controllers;

use App\Enums\OrderStatus;
use Illuminate\Support\Carbon;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class KitchenBoardControllerTest extends RestoCafeTestCase
{
    public function test_index_ok_kitchen(): void
    {
        $this->actingAs($this->kitchen())->get(route('kitchen.index'))->assertOk();
    }

    public function test_index_forbidden_for_waiter(): void
    {
        $this->actingAs($this->waiter())->get(route('kitchen.index'))->assertForbidden();
    }

    public function test_queue_returns_in_kitchen_orders_json(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::InKitchen]);

        $this->actingAs($this->kitchen())
            ->getJson(route('kitchen.queue'))
            ->assertOk()
            ->assertJsonStructure(['orders']);
    }

    public function test_ready_marks_order_and_redirects(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::InKitchen]);

        $this->actingAs($this->kitchen())
            ->patch(route('kitchen.ready', $order))
            ->assertRedirect();
        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);
    }

    public function test_ready_bad_state_flashes_error(): void
    {
        $order = $this->makeOrder($this->waiter());
        $this->actingAs($this->kitchen())
            ->patch(route('kitchen.ready', $order))
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_ready_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $order = $this->makeOrder($other['users']['waiter'], $other['table']);
        $order->update(['status' => OrderStatus::InKitchen]);

        $this->actingAs($this->kitchen())
            ->patch(route('kitchen.ready', $order))
            ->assertNotFound();
    }

    public function test_queue_excludes_orders_from_other_branches(): void
    {
        $other = $this->makeSecondaryBranch();
        $crossOrder = $this->makeOrder($other['users']['waiter'], $other['table']);
        $crossOrder->update(['status' => OrderStatus::InKitchen]);

        $resp = $this->actingAs($this->kitchen())
            ->getJson(route('kitchen.queue'))
            ->assertOk();

        $ids = collect($resp->json('orders'))->pluck('id')->all();
        $this->assertNotContains($crossOrder->id, $ids);
    }

    public function test_queue_orders_are_returned_oldest_first(): void
    {
        [$table1, $table2] = $this->availableTables(2);

        Carbon::setTestNow('2026-01-01 10:00:00');
        $older = $this->makeOrder($this->waiter(), $table1, OrderStatus::InKitchen);

        Carbon::setTestNow('2026-01-01 10:05:00');
        $newer = $this->makeOrder($this->waiter(), $table2, OrderStatus::InKitchen);

        Carbon::setTestNow();

        $resp = $this->actingAs($this->kitchen())
            ->getJson(route('kitchen.queue'))
            ->assertOk();

        $ids = collect($resp->json('orders'))->pluck('id')->values()->all();
        $this->assertSame([$older->id, $newer->id], $ids);
    }
}
