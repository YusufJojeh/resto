<?php

namespace Tests\Feature\RestoCafe;

use App\Modules\Assistant\Events\AssistantReplyCreated;
use App\Modules\Assistant\Models\AssistantConversation;
use Illuminate\Support\Facades\Event;

class AssistantWorkflowTest extends RestoCafeTestCase
{
    public function test_create_and_message_assistant_conversation(): void
    {
        $waiter = $this->waiter();
        Event::fake([AssistantReplyCreated::class]);

        $this->actingAs($waiter)
            ->post(route('assistant.conversations.store'), ['title' => 'Shift summary'])
            ->assertRedirect();

        $conversation = AssistantConversation::query()->latest()->firstOrFail();

        $this->actingAs($waiter)
            ->post(route('assistant.messages.store', $conversation), ['content' => 'summarize'])
            ->assertRedirect();

        $this->assertDatabaseHas('assistant_messages', [
            'assistant_conversation_id' => $conversation->id,
            'user_id' => $waiter->id,
            'role' => 'user',
        ]);

        $this->assertDatabaseHas('assistant_messages', [
            'assistant_conversation_id' => $conversation->id,
            'role' => 'assistant',
        ]);

        $this->assertDatabaseMissing('assistant_messages', [
            'assistant_conversation_id' => $conversation->id,
            'user_id' => $waiter->id,
            'role' => 'assistant',
        ]);

        Event::assertDispatched(AssistantReplyCreated::class);
    }

    public function test_block_unauthorized_assistant_access(): void
    {
        $conversation = AssistantConversation::query()->create([
            'branch_id' => $this->admin()->branch_id,
            'user_id' => $this->admin()->id,
            'title' => 'private',
        ]);

        $this->actingAs($this->waiter())
            ->get(route('assistant.show', $conversation))
            ->assertForbidden();
    }

    public function test_assistant_routes_respect_feature_flag(): void
    {
        config()->set('features.assistant.enabled', false);
        $waiter = $this->waiter();

        $this->actingAs($waiter)->get(route('assistant.index'))->assertNotFound();
        $this->actingAs($waiter)->post(route('assistant.conversations.store'), ['title' => 'x'])->assertNotFound();
    }
}
