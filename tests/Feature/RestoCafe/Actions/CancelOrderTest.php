<?php

namespace Tests\Feature\RestoCafe\Actions;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Modules\Orders\Actions\CancelOrder;
use App\Modules\Orders\Models\Order;
use App\Modules\Tables\Models\RestaurantTable;
use RuntimeException;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class CancelOrderTest extends RestoCafeTestCase
{
    public function test_cancels_new_order_and_frees_table(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);

        $updated = app(CancelOrder::class)->handle($order, 'customer left');

        $this->assertSame(OrderStatus::Cancelled, $updated->status);
        $this->assertSame('customer left', $updated->cancellation_reason);
        $this->assertNotNull($updated->cancelled_at);
        $this->assertSame(TableStatus::Available, RestaurantTable::query()->find($order->table_id)->status);
    }

    public function test_cancels_in_kitchen_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::InKitchen]);
        $updated = app(CancelOrder::class)->handle($order);
        $this->assertSame(OrderStatus::Cancelled, $updated->status);
        $this->assertNull($updated->cancellation_reason);
    }

    public function test_rejects_ready_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only pending kitchen orders can be cancelled.');
        app(CancelOrder::class)->handle($order);
    }

    public function test_rejects_served_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Served]);

        $this->expectException(RuntimeException::class);
        app(CancelOrder::class)->handle($order);
    }

    public function test_rejects_already_cancelled(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Cancelled]);

        $this->expectException(RuntimeException::class);
        app(CancelOrder::class)->handle($order);
    }
}
