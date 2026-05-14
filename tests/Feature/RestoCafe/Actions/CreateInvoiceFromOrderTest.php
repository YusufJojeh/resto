<?php

namespace Tests\Feature\RestoCafe\Actions;

use App\Enums\OrderStatus;
use App\Modules\Billing\Actions\CreateInvoiceFromOrder;
use App\Modules\Billing\Models\Invoice;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class CreateInvoiceFromOrderTest extends RestoCafeTestCase
{
    public function test_creates_invoice_from_ready_order_with_tax(): void
    {
        $cashier = $this->cashier();
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);

        $invoice = app(CreateInvoiceFromOrder::class)->handle($cashier, $order);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame(OrderStatus::Served, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->served_at);
        $this->assertNull($invoice->paid_at);
        $this->assertSame($cashier->id, $invoice->created_by);

        $subtotal = (float) $order->items->sum('subtotal');
        $this->assertEqualsWithDelta(round($subtotal * 0.10, 2), (float) $invoice->tax_amount, 0.01);
        $this->assertEqualsWithDelta(round($subtotal * 1.10, 2), (float) $invoice->total, 0.01);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{6}$/', $invoice->invoice_number);
    }

    public function test_rejects_duplicate_invoice(): void
    {
        $cashier = $this->cashier();
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);

        app(CreateInvoiceFromOrder::class)->handle($cashier, $order);

        // second call: order is now Served + invoice exists → invoice check fires first
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invoice already exists for this order.');
        app(CreateInvoiceFromOrder::class)->handle($cashier, $order->fresh());
    }

    public function test_rejects_non_ready_order(): void
    {
        $cashier = $this->cashier();
        $order = $this->makeOrder($this->waiter());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only ready orders can be invoiced.');
        app(CreateInvoiceFromOrder::class)->handle($cashier, $order);
    }

    public function test_rejects_served_order_without_existing_invoice(): void
    {
        $cashier = $this->cashier();
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Served]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only ready orders can be invoiced.');
        app(CreateInvoiceFromOrder::class)->handle($cashier, $order);
    }

    public function test_invoice_number_increments(): void
    {
        Carbon::setTestNow('2026-04-18 12:00:00');
        $cashier = $this->cashier();

        $tables = $this->availableTables(2);
        $waiter = $this->waiter();

        $a = $this->makeOrder($waiter, $tables[0]);
        $a->update(['status' => OrderStatus::Ready]);
        $b = $this->makeOrder($waiter, $tables[1]);
        $b->update(['status' => OrderStatus::Ready]);

        $i1 = app(CreateInvoiceFromOrder::class)->handle($cashier, $a);
        $i2 = app(CreateInvoiceFromOrder::class)->handle($cashier, $b);

        $this->assertSame('INV-2026-000001', $i1->invoice_number);
        $this->assertSame('INV-2026-000002', $i2->invoice_number);

        Carbon::setTestNow();
    }
}
