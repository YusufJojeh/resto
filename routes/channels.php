<?php

use App\Modules\Assistant\Models\AssistantConversation;
use App\Modules\Messaging\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    return (int) $user->branch_id === (int) $branchId;
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::query()->find($conversationId);
    if (! $conversation instanceof Conversation) {
        return false;
    }

    if ((int) $conversation->branch_id !== (int) $user->branch_id) {
        return false;
    }

    return $conversation->conversationParticipants()->where('user_id', $user->id)->exists();
});

Broadcast::channel('assistant.{conversationId}', function ($user, $conversationId) {
    $conversation = AssistantConversation::query()->find($conversationId);
    if (! $conversation instanceof AssistantConversation) {
        return false;
    }

    if ((int) $conversation->branch_id !== (int) $user->branch_id) {
        return false;
    }

    return (int) $conversation->user_id === (int) $user->id;
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
