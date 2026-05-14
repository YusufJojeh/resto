<?php

namespace App\Modules\Billing\Actions;

use App\Enums\OrderStatus;
use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\InvoiceSequence;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateInvoiceFromOrder
{
    public function handle(User $user, Order $order): Invoice
    {
        return DB::transaction(function () use ($user, $order) {
            $order->loadMissing(['items', 'branch', 'invoice']);

            if ($order->invoice) {
                throw new RuntimeException('Invoice already exists for this order.');
            }

            if ($order->status !== OrderStatus::Ready) {
                throw new RuntimeException('Only ready orders can be invoiced.');
            }

            $subtotal = (float) $order->items->sum('subtotal');
            $taxRate = (float) $order->branch->tax_rate;
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total = round($subtotal + $taxAmount, 2);

            $order->update([
                'status' => OrderStatus::Served,
                'served_at' => now(),
            ]);

            return Invoice::query()->create([
                'branch_id' => $order->branch_id,
                'order_id' => $order->id,
                'invoice_number' => $this->generateInvoiceNumber($order->branch_id),
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'total' => $total,
                'created_by' => $user->id,
            ])->load(['order.items', 'order.table', 'creator', 'branch']);
        });
    }

    private function generateInvoiceNumber(int $branchId): string
    {
        $year = (int) now()->format('Y');
        $sequence = InvoiceSequence::query()
            ->where('branch_id', $branchId)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if (! $sequence instanceof InvoiceSequence) {
            $sequence = InvoiceSequence::query()->create([
                'branch_id' => $branchId,
                'year' => $year,
                'next_number' => 2,
            ]);

            return sprintf('INV-%d-%06d', $year, 1);
        }

        $number = (int) $sequence->next_number;
        $sequence->update(['next_number' => $number + 1]);

        return sprintf('INV-%d-%06d', $year, $number);
    }
}
