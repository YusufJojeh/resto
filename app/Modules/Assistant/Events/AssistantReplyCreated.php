<?php

namespace App\Modules\Assistant\Events;

use App\Modules\Assistant\Models\AssistantMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssistantReplyCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly AssistantMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('assistant.'.$this->message->assistant_conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'assistant.reply';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->assistant_conversation_id,
            'role' => $this->message->role,
            'content' => $this->message->content,
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
