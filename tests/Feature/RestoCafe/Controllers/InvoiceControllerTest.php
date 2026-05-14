<?php

namespace Tests\Feature\RestoCafe\Controllers;

use App\Enums\OrderStatus;
use App\Modules\Billing\Actions\CreateInvoiceFromOrder;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class InvoiceControllerTest extends RestoCafeTestCase
{
    public function test_index_ok_cashier(): void
    {
        $this->actingAs($this->cashier())->get(route('invoices.index'))->assertOk();
    }

    public function test_index_forbidden_for_waiter(): void
    {
        $this->actingAs($this->waiter())->get(route('invoices.index'))->assertForbidden();
    }

    public function test_index_forbidden_for_kitchen(): void
    {
        $this->actingAs($this->kitchen())->get(route('invoices.index'))->assertForbidden();
    }

    public function test_show_invoice_ok(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order);

        $this->actingAs($this->cashier())
            ->get(route('invoices.show', $invoice))
            ->assertOk();
    }

    public function test_show_invoice_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $order = $this->makeOrder($other['users']['waiter'], $other['table']);
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($other['users']['cashier'], $order);

        $this->actingAs($this->cashier())
            ->get(route('invoices.show', $invoice))
            ->assertNotFound();
    }

    public function test_store_creates_invoice_and_redirects(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);

        $this->actingAs($this->cashier())
            ->post(route('invoices.store', $order))
            ->assertRedirect();
    }

    public function test_store_flashes_error_when_not_ready(): void
    {
        $order = $this->makeOrder($this->waiter());
        $this->actingAs($this->cashier())
            ->post(route('invoices.store', $order))
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_store_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $order = $this->makeOrder($other['users']['waiter'], $other['table']);
        $order->update(['status' => OrderStatus::Ready]);

        $this->actingAs($this->cashier())
            ->post(route('invoices.store', $order))
            ->assertNotFound();
    }

    public function test_pay_with_cash(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order);

        $this->actingAs($this->cashier())
            ->patch(route('invoices.pay', $invoice), ['payment_method' => 'cash'])
            ->assertRedirect(route('invoices.show', $invoice));
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_pay_validation_requires_payment_method(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order);

        $this->actingAs($this->cashier())
            ->patch(route('invoices.pay', $invoice), [])
            ->assertSessionHasErrors(['payment_method']);
    }

    public function test_pay_rejects_invalid_method(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order);

        $this->actingAs($this->cashier())
            ->patch(route('invoices.pay', $invoice), ['payment_method' => 'crypto'])
            ->assertSessionHasErrors(['payment_method']);
    }

    public function test_pay_forbidden_for_waiter(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order);

        $this->actingAs($this->waiter())
            ->patch(route('invoices.pay', $invoice), ['payment_method' => 'cash'])
            ->assertForbidden();
    }

    public function test_pay_double_flashes_error(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order);

        $this->actingAs($this->cashier())
            ->patch(route('invoices.pay', $invoice), ['payment_method' => 'cash']);
        $this->actingAs($this->cashier())
            ->patch(route('invoices.pay', $invoice->fresh()), ['payment_method' => 'card'])
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_pay_cross_branch_404(): void
    {
        $other = $this->makeSecondaryBranch();
        $order = $this->makeOrder($other['users']['waiter'], $other['table']);
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($other['users']['cashier'], $order);

        $this->actingAs($this->cashier())
            ->patch(route('invoices.pay', $invoice), ['payment_method' => 'cash'])
            ->assertNotFound();
    }
}
