<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Actions;

use App\Models\User;
use App\Modules\Assistant\Events\AssistantReplyCreated;
use App\Modules\Assistant\Models\AssistantConversation;
use App\Modules\Assistant\Models\AssistantMessage;
use App\Modules\Assistant\Support\AssistantRuntime;
use InvalidArgumentException;

class SendAssistantMessage
{
    public function __construct(
        private readonly AssistantRuntime $assistantRuntime,
    ) {}

    public function handle(
        User $user,
        AssistantConversation $conversation,
        string $content,
        ?string $currentPath = null,
        ?string $clientMessageId = null,
    ): AssistantMessage {
        $content = trim($content);

        if ($content === '') {
            throw new InvalidArgumentException('Assistant message content cannot be empty.');
        }

        if ($clientMessageId !== null) {
            $existingUserMessage = $conversation->messages()
                ->where('role', 'user')
                ->where('metadata->client_message_id', $clientMessageId)
                ->latest('id')
                ->first();

            if ($existingUserMessage instanceof AssistantMessage) {
                $existingReply = $conversation->messages()
                    ->where('role', 'assistant')
                    ->where('id', '>', $existingUserMessage->id)
                    ->orderBy('id')
                    ->first();

                if ($existingReply instanceof AssistantMessage) {
                    return $existingReply;
                }
            }
        }

        $conversation->messages()->create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $content,
            'metadata' => array_filter([
                'current_path' => $currentPath,
                'client_message_id' => $clientMessageId,
            ]),
        ]);

        $result = $this->assistantRuntime->respond(
            user: $user,
            conversation: $conversation,
            prompt: $content,
            currentPath: $currentPath,
        );

        $reply = trim((string) $result['content']);

        if ($reply === '') {
            $reply = 'Assistant returned an empty response.';
        }

        $assistantMessage = $conversation->messages()->create([
            'user_id' => null,
            'role' => 'assistant',
            'content' => $reply,
            'metadata' => [
                'intent' => $result['intent'] ?? null,
                'provider' => $result['provider'] ?? null,
                'used_fallback' => (bool) ($result['used_fallback'] ?? false),
                'denied_sensitive_context' => (bool) ($result['denied_sensitive_context'] ?? false),
                'runtime' => $result['metadata'] ?? [],
            ],
        ]);

        if ((bool) config('features.realtime.enabled', true)) {
            AssistantReplyCreated::dispatch($assistantMessage);
        }

        return $assistantMessage;
    }
}
