<?php

namespace App\Events;

use App\Modules\Tables\Models\RestaurantTable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly RestaurantTable $table,
        public readonly string $previousStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('branch.' . $this->table->branch_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'table.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->table->id,
            'branch_id' => $this->table->branch_id,
            'number' => $this->table->number,
            'name' => $this->table->name,
            'capacity' => $this->table->capacity,
            'status' => $this->table->status->value,
            'previous_status' => $this->previousStatus,
        ];
    }
}
