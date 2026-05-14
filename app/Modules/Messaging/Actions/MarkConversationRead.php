<?php

namespace App\Modules\Messaging\Actions;

use App\Models\User;
use App\Modules\Messaging\Models\Conversation;

class MarkConversationRead
{
    public function handle(User $user, Conversation $conversation): void
    {
        $conversation->conversationParticipants()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }
}
