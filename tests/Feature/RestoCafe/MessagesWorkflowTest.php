<?php

namespace Tests\Feature\RestoCafe;

use App\Modules\Messaging\Models\Conversation;

class MessagesWorkflowTest extends RestoCafeTestCase
{
    public function test_create_direct_conversation_and_send_message(): void
    {
        $waiter = $this->waiter();
        $cashier = $this->cashier();

        $this->actingAs($waiter)
            ->post(route('messages.conversations.store'), [
                'type' => 'direct',
                'title' => null,
                'participant_ids' => [$cashier->id],
            ])->assertRedirect();

        $conversation = Conversation::query()->latest()->firstOrFail();

        $this->actingAs($waiter)
            ->post(route('messages.messages.store', $conversation), ['body' => 'hello'])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'body' => 'hello',
        ]);
    }

    public function test_block_cross_branch_conversation_creation(): void
    {
        $other = $this->makeSecondaryBranch();

        $this->actingAs($this->waiter())
            ->post(route('messages.conversations.store'), [
                'type' => 'direct',
                'participant_ids' => [$other['users']['cashier']->id],
            ])
            ->assertSessionHas('error');
    }

    public function test_direct_conversation_rejects_more_than_two_users(): void
    {
        $waiter = $this->waiter();
        $cashier = $this->cashier();
        $manager = $this->manager();

        $this->actingAs($waiter)
            ->post(route('messages.conversations.store'), [
                'type' => 'direct',
                'participant_ids' => [$cashier->id, $manager->id],
            ])
            ->assertSessionHasErrors('participant_ids');
    }
}
