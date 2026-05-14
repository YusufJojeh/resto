<?php

namespace Tests\Feature\RestoCafe\Actions;

use App\Enums\OrderStatus;
use App\Modules\Kitchen\Actions\MarkKitchenOrderReady;
use RuntimeException;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class MarkKitchenOrderReadyTest extends RestoCafeTestCase
{
    public function test_marks_in_kitchen_order_ready(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::InKitchen]);

        $updated = app(MarkKitchenOrderReady::class)->handle($order);
        $this->assertSame(OrderStatus::Ready, $updated->status);
    }

    public function test_rejects_new_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only kitchen orders can be marked ready.');
        app(MarkKitchenOrderReady::class)->handle($order);
    }

    public function test_rejects_already_ready(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $this->expectException(RuntimeException::class);
        app(MarkKitchenOrderReady::class)->handle($order);
    }

    public function test_rejects_served_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Served]);
        $this->expectException(RuntimeException::class);
        app(MarkKitchenOrderReady::class)->handle($order);
    }

    public function test_rejects_cancelled_order(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Cancelled]);
        $this->expectException(RuntimeException::class);
        app(MarkKitchenOrderReady::class)->handle($order);
    }
}
