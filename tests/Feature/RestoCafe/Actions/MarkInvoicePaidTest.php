<?php

namespace Tests\Feature\RestoCafe\Actions;

use App\Enums\InvoicePaymentMethod;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Modules\Billing\Actions\CreateInvoiceFromOrder;
use App\Modules\Billing\Actions\MarkInvoicePaid;
use App\Modules\Tables\Models\RestaurantTable;
use RuntimeException;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class MarkInvoicePaidTest extends RestoCafeTestCase
{
    public function test_pays_invoice_with_cash_and_frees_table(): void
    {
        $cashier = $this->cashier();
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($cashier, $order);

        $paid = app(MarkInvoicePaid::class)->handle($invoice, InvoicePaymentMethod::Cash);

        $this->assertNotNull($paid->paid_at);
        $this->assertSame(InvoicePaymentMethod::Cash, $paid->payment_method);
        $this->assertSame(TableStatus::Available, RestaurantTable::query()->find($order->table_id)->status);
    }

    public function test_pays_invoice_with_card(): void
    {
        $cashier = $this->cashier();
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($cashier, $order);

        $paid = app(MarkInvoicePaid::class)->handle($invoice, InvoicePaymentMethod::Card);
        $this->assertSame(InvoicePaymentMethod::Card, $paid->payment_method);
    }

    public function test_rejects_double_payment(): void
    {
        $cashier = $this->cashier();
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($cashier, $order);

        app(MarkInvoicePaid::class)->handle($invoice, InvoicePaymentMethod::Cash);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invoice is already paid.');
        app(MarkInvoicePaid::class)->handle($invoice->fresh(), InvoicePaymentMethod::Card);
    }
}
