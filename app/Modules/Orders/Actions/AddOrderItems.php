<?php

namespace App\Modules\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\User;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AddOrderItems
{
    public function handle(User $user, Order $order, array $items): Order
    {
        return DB::transaction(function () use ($user, $order, $items) {
            $order->refresh();

            if ($order->status !== OrderStatus::New) {
                throw new RuntimeException('Only pending orders can be modified.');
            }

            foreach ($items as $itemPayload) {
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

            return $order->load('items');
        });
    }
}
