<?php

namespace App\Events;

use App\Modules\Orders\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Order $order)
    {
        $this->order->loadMissing(['table', 'items', 'user']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('branch.' . $this->order->branch_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'branch_id' => $this->order->branch_id,
            'table_id' => $this->order->table_id,
            'table_number' => $this->order->table?->number,
            'status' => $this->order->status->value,
            'notes' => $this->order->notes,
            'items_count' => $this->order->items->count(),
            'created_at' => $this->order->created_at?->toISOString(),
        ];
    }
}
