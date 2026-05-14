<?php

namespace App\Events;

use App\Modules\Orders\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $previousStatus,
    ) {
        $this->order->loadMissing(['table', 'items']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('branch.' . $this->order->branch_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'branch_id' => $this->order->branch_id,
            'table_id' => $this->order->table_id,
            'table_number' => $this->order->table?->number,
            'status' => $this->order->status->value,
            'previous_status' => $this->previousStatus,
            'items' => $this->order->items->map(fn ($item) => [
                'id' => $item->id,
                'menu_item_name' => $item->menu_item_name,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
            ])->toArray(),
            'updated_at' => $this->order->updated_at?->toISOString(),
        ];
    }
}
