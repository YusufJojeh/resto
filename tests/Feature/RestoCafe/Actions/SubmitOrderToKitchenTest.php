<?php

namespace Tests\Feature\RestoCafe\Actions;

use App\Enums\OrderStatus;
use App\Modules\Orders\Actions\SubmitOrderToKitchen;
use RuntimeException;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class SubmitOrderToKitchenTest extends RestoCafeTestCase
{
    public function test_submits_new_order_to_kitchen(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);

        $updated = app(SubmitOrderToKitchen::class)->handle($order);
        $this->assertSame(OrderStatus::InKitchen, $updated->status);
    }

    public function test_rejects_already_in_kitchen(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::InKitchen]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only new orders can be sent to the kitchen.');
        app(SubmitOrderToKitchen::class)->handle($order);
    }

    public function test_rejects_served_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Served]);

        $this->expectException(RuntimeException::class);
        app(SubmitOrderToKitchen::class)->handle($order);
    }

    public function test_rejects_cancelled_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Cancelled]);

        $this->expectException(RuntimeException::class);
        app(SubmitOrderToKitchen::class)->handle($order);
    }

    public function test_rejects_ready_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);

        $this->expectException(RuntimeException::class);
        app(SubmitOrderToKitchen::class)->handle($order);
    }

    /**
     * Simulates a concurrent transition: caller holds a stale $order (status=New
     * in-memory), but a parallel request already moved the row to InKitchen.
     * The action must re-read under lock and throw, not silently double-fire.
     */
    public function test_rejects_when_status_changed_between_read_and_lock(): void
    {
        $order = $this->makeOrder($this->waiter());
        $this->assertSame(OrderStatus::New, $order->status);

        // Simulate the parallel writer.
        \App\Modules\Orders\Models\Order::query()
            ->whereKey($order->getKey())
            ->update(['status' => OrderStatus::InKitchen]);

        // Caller still holds the stale snapshot.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only new orders can be sent to the kitchen.');
        app(SubmitOrderToKitchen::class)->handle($order);
    }
}
