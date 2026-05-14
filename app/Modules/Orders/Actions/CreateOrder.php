<?php

namespace App\Modules\Orders\Actions;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Events\OrderCreated;
use App\Events\TableStatusChanged;
use App\Models\User;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Orders\Models\Order;
use App\Modules\Tables\Models\RestaurantTable;
use App\Notifications\OperationalNotification;
use App\Support\Notifications\BranchRoleNotifier;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateOrder
{
    public function handle(User $user, array $payload): Order
    {
        return DB::transaction(function () use ($user, $payload) {
            $table = RestaurantTable::query()
                ->whereKey($payload['table_id'])
                ->where('branch_id', $user->branch_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($table->status !== TableStatus::Available) {
                throw new RuntimeException('Table is already occupied or reserved.');
            }

            $order = Order::query()->create([
                'branch_id' => $user->branch_id,
                'table_id' => $table->id,
                'user_id' => $user->id,
                'status' => OrderStatus::New,
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($payload['items'] as $itemPayload) {
                $menuItem = MenuItem::query()
                    ->whereKey($itemPayload['menu_item_id'])
                    ->where('branch_id', $user->branch_id)
                    ->where('is_available', true)
                    ->firstOrFail();

                $order->items()->create([
                    'menu_item_id' => $menuItem->id,
                    'menu_item_name' => $menuItem->name,
                    'unit_price' => $menuItem->price,
                    'quantity' => $itemPayload['quantity'],
                    'subtotal' => bcmul((string) $menuItem->price, (string) $itemPayload['quantity'], 2),
                    'notes' => $itemPayload['notes'] ?? null,
                ]);
            }

            $previousTableStatus = $table->status->value;
            $table->update(['status' => TableStatus::Occupied]);

            $order->load(['items', 'table', 'user']);

            OrderCreated::dispatch($order);
            TableStatusChanged::dispatch($table->refresh(), $previousTableStatus);
            app(BranchRoleNotifier::class)->notifyByRoles(
                (int) $user->branch_id,
                ['admin', 'manager', 'kitchen'],
                new OperationalNotification(
                    'new_order',
                    'New order created',
                    "Order #{$order->id} was created and is pending kitchen submission.",
                    (int) $user->branch_id,
                ),
            );

            return $order;
        });
    }
}
