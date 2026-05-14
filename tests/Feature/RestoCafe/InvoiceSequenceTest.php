<?php

namespace Tests\Feature\RestoCafe;

use App\Modules\Billing\Actions\CreateInvoiceFromOrder;
use App\Modules\Billing\Models\InvoiceSequence;

class InvoiceSequenceTest extends RestoCafeTestCase
{
    public function test_invoice_sequence_is_persisted_per_branch_and_year(): void
    {
        $order1 = $this->makeOrder($this->waiter());
        $order1->update(['status' => 'ready']);
        $order2 = $this->makeOrder($this->waiter(), $this->availableTables(2)[1]);
        $order2->update(['status' => 'ready']);

        app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order1);
        app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order2);

        $this->assertDatabaseCount('invoice_sequences', 1);
        $this->assertSame(3, (int) InvoiceSequence::query()->firstOrFail()->next_number);
    }
}
