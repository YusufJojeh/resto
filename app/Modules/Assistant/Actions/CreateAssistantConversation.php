<?php

namespace App\Modules\Assistant\Actions;

use App\Models\User;
use App\Modules\Assistant\Models\AssistantConversation;

class CreateAssistantConversation
{
    public function handle(User $user, ?string $title = null): AssistantConversation
    {
        return AssistantConversation::query()->create([
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'title' => $title,
        ]);
    }
}
