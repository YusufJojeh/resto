<?php

namespace App\Http\Controllers;

use App\Modules\Messaging\Actions\CreateConversation;
use App\Modules\Messaging\Actions\MarkConversationRead;
use App\Modules\Messaging\Actions\SendMessage;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Requests\StoreConversationRequest;
use App\Modules\Messaging\Requests\StoreMessageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class MessagesController extends Controller
{
    public function __construct(
        private readonly CreateConversation $createConversation,
        private readonly SendMessage $sendMessage,
        private readonly MarkConversationRead $markConversationRead,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless((bool) config('features.messages.enabled', true), 404);

        $user = $request->user();
        $activeId = $request->integer('conversation');

        $conversations = Conversation::query()
            ->where('branch_id', $user->branch_id)
            ->whereHas('conversationParticipants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['participants:id,name', 'messages' => fn ($q) => $q->latest()->limit(1), 'conversationParticipants' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderByDesc('last_message_at')
            ->paginate(20)
            ->withQueryString();

        $active = null;
        if ($activeId > 0) {
            $active = Conversation::query()
                ->where('branch_id', $user->branch_id)
                ->whereKey($activeId)
                ->whereHas('conversationParticipants', fn ($q) => $q->where('user_id', $user->id))
                ->with(['participants:id,name', 'messages.sender:id,name'])
                ->firstOrFail();

            $this->markConversationRead->handle($user, $active);
        }

        return Inertia::render('messages/index', [
            'conversations' => $conversations,
            'activeConversation' => $active,
            'users' => \App\Models\User::query()->where('branch_id', $user->branch_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        abort_unless((bool) config('features.messages.enabled', true), 404);
        $this->assertParticipant($request->user()->id, $request->user()->branch_id, $conversation);

        $this->markConversationRead->handle($request->user(), $conversation);

        return Inertia::render('messages/index', [
            'conversations' => Conversation::query()
                ->where('branch_id', $request->user()->branch_id)
                ->whereHas('conversationParticipants', fn ($q) => $q->where('user_id', $request->user()->id))
                ->with(['participants:id,name', 'messages' => fn ($q) => $q->latest()->limit(1), 'conversationParticipants' => fn ($q) => $q->where('user_id', $request->user()->id)])
                ->orderByDesc('last_message_at')
                ->paginate(20)
                ->withQueryString(),
            'activeConversation' => $conversation->load(['participants:id,name', 'messages.sender:id,name']),
            'users' => \App\Models\User::query()->where('branch_id', $request->user()->branch_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeConversation(StoreConversationRequest $request): RedirectResponse
    {
        abort_unless((bool) config('features.messages.enabled', true), 404);
        try {
            $conversation = $this->createConversation->handle($request->user(), $request->validated());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('messages.show', $conversation);
    }

    public function storeMessage(StoreMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        abort_unless((bool) config('features.messages.enabled', true), 404);
        $this->assertParticipant($request->user()->id, $request->user()->branch_id, $conversation);

        try {
            $this->sendMessage->handle($request->user(), $conversation, $request->validated('body'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    public function markRead(Request $request, Conversation $conversation): RedirectResponse
    {
        abort_unless((bool) config('features.messages.enabled', true), 404);
        $this->assertParticipant($request->user()->id, $request->user()->branch_id, $conversation);

        $this->markConversationRead->handle($request->user(), $conversation);

        return back();
    }

    private function assertParticipant(int $userId, ?int $branchId, Conversation $conversation): void
    {
        abort_unless((int) $conversation->branch_id === (int) $branchId, 404);
        abort_unless($conversation->conversationParticipants()->where('user_id', $userId)->exists(), 403);
    }
}
