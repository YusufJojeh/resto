<?php

namespace Tests\Feature\RestoCafe;

use App\Modules\Assistant\Models\AssistantConversation;
use App\Modules\Messaging\Models\Conversation;

class RealtimeChannelAuthorizationTest extends RestoCafeTestCase
{
    public function test_non_participant_cannot_open_conversation_view(): void
    {
        $waiter = $this->waiter();
        $cashier = $this->cashier();

        $conversation = Conversation::query()->create([
            'branch_id' => $waiter->branch_id,
            'type' => 'direct',
            'created_by' => $waiter->id,
        ]);
        $conversation->conversationParticipants()->createMany([
            ['user_id' => $waiter->id],
            ['user_id' => $cashier->id],
        ]);

        $this->actingAs($this->kitchen())->get(route('messages.show', $conversation))->assertForbidden();
    }

    public function test_non_owner_cannot_access_assistant_conversation(): void
    {
        $admin = $this->admin();
        $conversation = AssistantConversation::query()->create([
            'branch_id' => $admin->branch_id,
            'user_id' => $admin->id,
            'title' => 'secret',
        ]);

        $this->actingAs($this->waiter())->get(route('assistant.show', $conversation))->assertForbidden();
    }
}
