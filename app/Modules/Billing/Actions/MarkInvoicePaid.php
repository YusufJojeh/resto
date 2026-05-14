<?php

namespace App\Modules\Billing\Actions;

use App\Enums\InvoicePaymentMethod;
use App\Enums\TableStatus;
use App\Notifications\OperationalNotification;
use App\Modules\Billing\Models\Invoice;
use App\Support\Notifications\BranchRoleNotifier;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MarkInvoicePaid
{
    public function handle(Invoice $invoice, InvoicePaymentMethod $paymentMethod): Invoice
    {
        return DB::transaction(function () use ($invoice, $paymentMethod) {
            $locked = Invoice::query()
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->with('order.table')
                ->firstOrFail();

            if ($locked->paid_at) {
                throw new RuntimeException('Invoice is already paid.');
            }

            $locked->update([
                'payment_method' => $paymentMethod,
                'paid_at' => now(),
            ]);

            $locked->order->table()->update(['status' => TableStatus::Available]);
            app(BranchRoleNotifier::class)->notifyByRoles(
                (int) $locked->branch_id,
                ['cashier', 'admin', 'manager'],
                new OperationalNotification(
                    'invoice_paid',
                    'Invoice paid',
                    "Invoice {$locked->invoice_number} was marked paid.",
                    (int) $locked->branch_id,
                ),
            );

            return $locked->refresh()->load(['order.items', 'order.table', 'creator', 'branch']);
        });
    }
}
