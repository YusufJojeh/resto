<?php

namespace Tests\Feature\RestoCafe\Actions;

use App\Enums\OrderStatus;
use App\Modules\Orders\Actions\AddOrderItems;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class AddOrderItemsTest extends RestoCafeTestCase
{
    public function test_adds_items_to_new_order(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        [$mi1, $mi2] = $this->menuItems(2);

        $updated = app(AddOrderItems::class)->handle($waiter, $order, [
            ['menu_item_id' => $mi1->id, 'quantity' => 1],
            ['menu_item_id' => $mi2->id, 'quantity' => 4, 'notes' => 'extra'],
        ]);

        $this->assertSame(3, $updated->items->count());
        $this->assertSame('extra', $updated->items->firstWhere('menu_item_id', $mi2->id)->notes);
    }

    public function test_cannot_add_items_to_in_kitchen_order(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $order->update(['status' => OrderStatus::InKitchen]);
        $mi = $this->menuItems(1)[0];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only pending orders can be modified.');

        app(AddOrderItems::class)->handle($waiter, $order, [
            ['menu_item_id' => $mi->id, 'quantity' => 1],
        ]);
    }

    public function test_cannot_add_items_to_ready_order(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $order->update(['status' => OrderStatus::Ready]);
        $mi = $this->menuItems(1)[0];

        $this->expectException(RuntimeException::class);
        app(AddOrderItems::class)->handle($waiter, $order, [
            ['menu_item_id' => $mi->id, 'quantity' => 1],
        ]);
    }

    public function test_cannot_add_items_to_cancelled_order(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $order->update(['status' => OrderStatus::Cancelled]);
        $mi = $this->menuItems(1)[0];

        $this->expectException(RuntimeException::class);
        app(AddOrderItems::class)->handle($waiter, $order, [
            ['menu_item_id' => $mi->id, 'quantity' => 1],
        ]);
    }

    public function test_rejects_cross_branch_menu_item(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $other = $this->makeSecondaryBranch();

        $this->expectException(ModelNotFoundException::class);
        app(AddOrderItems::class)->handle($waiter, $order, [
            ['menu_item_id' => $other['menuItem']->id, 'quantity' => 1],
        ]);
    }

    public function test_rejects_unavailable_menu_item(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $mi = $this->menuItems(1)[0];
        $mi->update(['is_available' => false]);

        $this->expectException(ModelNotFoundException::class);
        app(AddOrderItems::class)->handle($waiter, $order, [
            ['menu_item_id' => $mi->id, 'quantity' => 1],
        ]);
    }
}
