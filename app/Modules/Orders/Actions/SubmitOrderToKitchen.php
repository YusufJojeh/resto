<?php

namespace App\Modules\Orders\Actions;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Notifications\OperationalNotification;
use App\Modules\Orders\Models\Order;
use App\Support\Notifications\BranchRoleNotifier;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubmitOrderToKitchen
{
    public function handle(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $locked = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->status->canTransitionTo(OrderStatus::InKitchen)) {
                throw new RuntimeException('Only new orders can be sent to the kitchen.');
            }

            $previousStatus = $locked->status->value;
            $locked->update(['status' => OrderStatus::InKitchen]);
            $locked->refresh();

            OrderStatusChanged::dispatch($locked, $previousStatus);
            app(BranchRoleNotifier::class)->notifyByRoles(
                (int) $locked->branch_id,
                ['kitchen', 'admin', 'manager'],
                new OperationalNotification(
                    'kitchen_ticket_updated',
                    'Order sent to kitchen',
                    "Order #{$locked->id} is now in kitchen queue.",
                    (int) $locked->branch_id,
                ),
            );

            return $locked;
        });
    }
}
