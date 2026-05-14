<?php

namespace App\Modules\Orders\Actions;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Events\OrderStatusChanged;
use App\Events\TableStatusChanged;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CancelOrder
{
    public function handle(Order $order, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason) {
            $locked = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status, [OrderStatus::New, OrderStatus::InKitchen], true)) {
                throw new RuntimeException('Only pending kitchen orders can be cancelled.');
            }

            $previousOrderStatus = $locked->status->value;

            $locked->update([
                'status' => OrderStatus::Cancelled,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            $table = $locked->table;
            $previousTableStatus = $table?->status?->value ?? 'occupied';
            $locked->table()->update(['status' => TableStatus::Available]);

            $locked->refresh();

            OrderStatusChanged::dispatch($locked, $previousOrderStatus);

            if ($table) {
                TableStatusChanged::dispatch($table->refresh(), $previousTableStatus);
            }

            return $locked;
        });
    }
}
