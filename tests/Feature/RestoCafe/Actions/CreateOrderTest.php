<?php

namespace Tests\Feature\RestoCafe\Actions;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Modules\Orders\Actions\CreateOrder;
use App\Modules\Tables\Models\RestaurantTable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class CreateOrderTest extends RestoCafeTestCase
{
    public function test_creates_order_with_items_and_occupies_table(): void
    {
        $waiter = $this->waiter();
        $table = $this->firstTable();
        [$mi1, $mi2] = $this->menuItems(2);

        $order = app(CreateOrder::class)->handle($waiter, [
            'table_id' => $table->id,
            'notes' => 'rush',
            'items' => [
                ['menu_item_id' => $mi1->id, 'quantity' => 2, 'notes' => 'hot'],
                ['menu_item_id' => $mi2->id, 'quantity' => 3, 'notes' => null],
            ],
        ]);

        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame('rush', $order->notes);
        $this->assertSame(2, $order->items->count());
        $this->assertSame(TableStatus::Occupied, $table->fresh()->status);
        $this->assertSame($waiter->id, $order->user_id);
        $this->assertSame(1, $order->branch_id);

        $subtotalItem1 = bcmul((string) $mi1->price, '2', 2);
        $this->assertEquals($subtotalItem1, $order->items->firstWhere('menu_item_id', $mi1->id)->subtotal);
    }

    public function test_rejects_occupied_table(): void
    {
        $waiter = $this->waiter();
        $table = $this->firstTable();
        $table->update(['status' => TableStatus::Occupied]);
        $mi = $this->menuItems(1)[0];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table is already occupied or reserved.');

        app(CreateOrder::class)->handle($waiter, [
            'table_id' => $table->id,
            'items' => [
                ['menu_item_id' => $mi->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_rejects_reserved_table(): void
    {
        $waiter = $this->waiter();
        $table = $this->firstTable();
        $table->update(['status' => TableStatus::Reserved]);
        $mi = $this->menuItems(1)[0];

        $this->expectException(RuntimeException::class);
        app(CreateOrder::class)->handle($waiter, [
            'table_id' => $table->id,
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 1]],
        ]);
    }

    public function test_rejects_cross_branch_table(): void
    {
        $waiter = $this->waiter();
        $other = $this->makeSecondaryBranch();
        $mi = $this->menuItems(1)[0];

        $this->expectException(ModelNotFoundException::class);
        app(CreateOrder::class)->handle($waiter, [
            'table_id' => $other['table']->id,
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 1]],
        ]);
    }

    public function test_rejects_cross_branch_menu_item(): void
    {
        $waiter = $this->waiter();
        $other = $this->makeSecondaryBranch();
        $table = $this->firstTable();

        $this->expectException(ModelNotFoundException::class);
        app(CreateOrder::class)->handle($waiter, [
            'table_id' => $table->id,
            'items' => [['menu_item_id' => $other['menuItem']->id, 'quantity' => 1]],
        ]);
    }

    public function test_rejects_unavailable_menu_item(): void
    {
        $waiter = $this->waiter();
        $table = $this->firstTable();
        $mi = $this->menuItems(1)[0];
        $mi->update(['is_available' => false]);

        $this->expectException(ModelNotFoundException::class);
        app(CreateOrder::class)->handle($waiter, [
            'table_id' => $table->id,
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 1]],
        ]);
    }

    public function test_notes_default_to_null(): void
    {
        $waiter = $this->waiter();
        $table = $this->firstTable();
        $mi = $this->menuItems(1)[0];

        $order = app(CreateOrder::class)->handle($waiter, [
            'table_id' => $table->id,
            'items' => [['menu_item_id' => $mi->id, 'quantity' => 1]],
        ]);

        $this->assertNull($order->notes);
        $this->assertNull($order->items->first()->notes);
    }

    public function test_rollback_on_invalid_item_does_not_persist_order(): void
    {
        $waiter = $this->waiter();
        $table = $this->firstTable();
        $mi = $this->menuItems(1)[0];

        try {
            app(CreateOrder::class)->handle($waiter, [
                'table_id' => $table->id,
                'items' => [
                    ['menu_item_id' => $mi->id, 'quantity' => 1],
                    ['menu_item_id' => 99999, 'quantity' => 1],
                ],
            ]);
            $this->fail('expected ModelNotFoundException');
        } catch (ModelNotFoundException) {
            // expected
        }

        $this->assertSame(0, \App\Modules\Orders\Models\Order::query()->count());
        $this->assertSame(TableStatus::Available, RestaurantTable::query()->find($table->id)->status);
    }
}
