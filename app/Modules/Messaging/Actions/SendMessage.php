<?php

namespace App\Modules\Messaging\Actions;

use App\Models\User;
use App\Modules\Messaging\Events\MessageSent;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Models\Message;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SendMessage
{
    public function handle(User $user, Conversation $conversation, string $body): Message
    {
        return DB::transaction(function () use ($user, $conversation, $body): Message {
            $isParticipant = $conversation->conversationParticipants()->where('user_id', $user->id)->exists();
            if (! $isParticipant) {
                throw new RuntimeException('Not allowed to send in this conversation.');
            }

            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'body' => strip_tags($body),
            ]);

            $conversation->update(['last_message_at' => now()]);
            $conversation->conversationParticipants()->where('user_id', '!=', $user->id)->update(['last_read_at' => null]);
            $conversation->conversationParticipants()->where('user_id', $user->id)->update(['last_read_at' => now()]);

            $message->load('sender');
            MessageSent::dispatch($message);

            return $message;
        });
    }
}
