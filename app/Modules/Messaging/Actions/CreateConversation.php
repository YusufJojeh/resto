<?php

namespace App\Modules\Messaging\Actions;

use App\Models\User;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Models\ConversationParticipant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateConversation
{
    public function handle(User $actor, array $payload): Conversation
    {
        $participantIds = collect($payload['participant_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        if (! $participantIds->contains((int) $actor->id)) {
            $participantIds->push((int) $actor->id);
        }

        return DB::transaction(function () use ($actor, $payload, $participantIds): Conversation {
            $participants = User::query()
                ->whereIn('id', $participantIds->all())
                ->where('branch_id', $actor->branch_id)
                ->get();

            if ($participants->count() !== $participantIds->count()) {
                throw new RuntimeException('Invalid participants.');
            }

            $type = (string) $payload['type'];
            if ($type === 'group' && ! $actor->hasAnyRole(['admin', 'manager'])) {
                throw new RuntimeException('Only admins or managers can create group conversations.');
            }

            if ($type === 'direct') {
                $existing = Conversation::query()
                    ->where('branch_id', $actor->branch_id)
                    ->where('type', 'direct')
                    ->whereHas('conversationParticipants', fn ($q) => $q->whereIn('user_id', $participantIds->all()))
                    ->withCount('conversationParticipants')
                    ->get()
                    ->first(fn ($conversation) => (int) $conversation->conversation_participants_count === $participantIds->count());

                if ($existing instanceof Conversation) {
                    return $existing;
                }
            }

            $conversation = Conversation::query()->create([
                'branch_id' => $actor->branch_id,
                'title' => $payload['title'] ?? null,
                'type' => $type,
                'created_by' => $actor->id,
            ]);

            foreach ($participantIds as $participantId) {
                ConversationParticipant::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $participantId,
                    'last_read_at' => $participantId === (int) $actor->id ? now() : null,
                ]);
            }

            return $conversation->load('participants');
        });
    }
}
