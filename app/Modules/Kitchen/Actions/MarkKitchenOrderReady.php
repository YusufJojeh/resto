<?php

namespace App\Modules\Kitchen\Actions;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Notifications\OperationalNotification;
use App\Modules\Orders\Models\Order;
use App\Support\Notifications\BranchRoleNotifier;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MarkKitchenOrderReady
{
    public function handle(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $locked = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->status->canTransitionTo(OrderStatus::Ready)) {
                throw new RuntimeException('Only kitchen orders can be marked ready.');
            }

            $previousStatus = $locked->status->value;
            $locked->update(['status' => OrderStatus::Ready]);
            $locked->refresh();

            OrderStatusChanged::dispatch($locked, $previousStatus);
            app(BranchRoleNotifier::class)->notifyByRoles(
                (int) $locked->branch_id,
                ['waiter', 'cashier', 'admin', 'manager'],
                new OperationalNotification(
                    'order_status_changed',
                    'Order marked ready',
                    "Order #{$locked->id} is ready for service/invoicing.",
                    (int) $locked->branch_id,
                ),
            );

            return $locked;
        });
    }
}
