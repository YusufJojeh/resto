<?php

namespace Tests\Feature\RestoCafe;

class AssistantRateLimitTest extends RestoCafeTestCase
{
    public function test_assistant_panel_messages_are_throttled(): void
    {
        config()->set('assistant.rate_limit', 1);

        $user = $this->waiter();

        $this->actingAs($user)->postJson(route('assistant.panel.messages.store'), [
            'content' => 'First message',
            'current_path' => '/dashboard',
            'client_message_id' => 'rate-1',
        ])->assertOk();

        $this->actingAs($user)->postJson(route('assistant.panel.messages.store'), [
            'content' => 'Second message',
            'current_path' => '/dashboard',
            'client_message_id' => 'rate-2',
        ])->assertStatus(429);
    }
}
