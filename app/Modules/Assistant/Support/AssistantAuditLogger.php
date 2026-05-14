<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

use App\Models\User;
use App\Modules\Assistant\Models\AssistantConversation;
use Illuminate\Support\Facades\Log;

final class AssistantAuditLogger
{
    public function log(
        User $user,
        AssistantConversation $conversation,
        string $intent,
        string $provider,
        bool $deniedSensitiveContext,
        float $latencyMs,
        bool $success,
        ?string $failureReason = null,
    ): void {
        Log::info('assistant.audit', [
            'user_id' => (int) $user->id,
            'role' => $user->getRoleNames()->first(),
            'branch_id' => $user->branch_id,
            'conversation_id' => (int) $conversation->id,
            'intent' => $intent,
            'provider' => $provider,
            'denied_sensitive_context' => $deniedSensitiveContext,
            'latency_ms' => round($latencyMs, 2),
            'success' => $success,
            'failure_reason' => $failureReason,
        ]);
    }
}
