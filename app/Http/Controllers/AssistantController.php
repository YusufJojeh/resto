<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Assistant\Actions\CreateAssistantConversation;
use App\Modules\Assistant\Actions\SendAssistantMessage;
use App\Modules\Assistant\Models\AssistantConversation;
use App\Modules\Assistant\Requests\StoreAssistantConversationRequest;
use App\Modules\Assistant\Requests\StoreAssistantMessageRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AssistantController extends Controller
{
    public function __construct(
        private readonly CreateAssistantConversation $createAssistantConversation,
        private readonly SendAssistantMessage $sendAssistantMessage,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless((bool) config('assistant.enabled', true) && (bool) config('features.assistant.enabled', true), 404);

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('assistant', [
            'conversations' => $this->conversationQuery($user)
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),

            'activeConversation' => null,
        ]);
    }

    public function storeConversation(StoreAssistantConversationRequest $request): RedirectResponse
    {
        abort_unless((bool) config('assistant.enabled', true) && (bool) config('features.assistant.enabled', true), 404);

        /** @var User $user */
        $user = $request->user();

        $conversation = $this->createAssistantConversation->handle(
            $user,
            $request->validated('title'),
        );

        return to_route('assistant.show', $conversation);
    }

    public function show(Request $request, AssistantConversation $conversation): Response
    {
        abort_unless((bool) config('assistant.enabled', true) && (bool) config('features.assistant.enabled', true), 404);

        /** @var User $user */
        $user = $request->user();

        $this->assertOwnedConversation(
            userId: (int) $user->id,
            branchId: $user->branch_id,
            conversation: $conversation,
        );

        return Inertia::render('assistant', [
            'conversations' => $this->conversationQuery($user)
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),

            'activeConversation' => $conversation->load([
                'messages' => fn ($query) => $query->oldest('id'),
            ]),
        ]);
    }

    public function storeMessage(
        StoreAssistantMessageRequest $request,
        AssistantConversation $conversation,
    ): RedirectResponse {
        abort_unless((bool) config('assistant.enabled', true) && (bool) config('features.assistant.enabled', true), 404);

        /** @var User $user */
        $user = $request->user();

        $this->assertOwnedConversation(
            userId: (int) $user->id,
            branchId: $user->branch_id,
            conversation: $conversation,
        );

        $this->sendAssistantMessage->handle(
            user: $user,
            conversation: $conversation,
            content: $request->validated('content'),
            currentPath: $request->validated('current_path'),
            clientMessageId: $request->validated('client_message_id'),
        );

        return back();
    }

    public function storePanelMessage(StoreAssistantMessageRequest $request): JsonResponse
    {
        abort_unless((bool) config('assistant.enabled', true) && (bool) config('features.assistant.enabled', true), 404);

        /** @var User|null $user */
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        try {
            $conversation = $this->resolvePanelConversation($user);

            $assistantMessage = $this->sendAssistantMessage->handle(
                user: $user,
                conversation: $conversation,
                content: $request->validated('content'),
                currentPath: $request->validated('current_path'),
                clientMessageId: $request->validated('client_message_id'),
            );

            return response()->json([
                'conversation' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                ],

                'message' => [
                    'id' => $assistantMessage->id,
                    'role' => $assistantMessage->role,
                    'content' => $assistantMessage->content,
                ],

                'queued' => false,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => __('The assistant could not complete your request right now.'),

                'message' => [
                    'role' => 'assistant',
                    'content' => __('The assistant could not complete your request right now.'),
                ],
            ], 500);
        }
    }

    private function resolvePanelConversation(User $user): AssistantConversation
    {
        $conversation = $this->conversationQuery($user)
            ->where('title', 'Assistant Panel')
            ->latest('id')
            ->first();

        if ($conversation instanceof AssistantConversation) {
            return $conversation;
        }

        return $this->createAssistantConversation->handle(
            $user,
            'Assistant Panel',
        );
    }

    private function conversationQuery(User $user): Builder
    {
        return AssistantConversation::query()
            ->where('branch_id', $user->branch_id)
            ->where('user_id', $user->id);
    }

    private function assertOwnedConversation(
        int $userId,
        ?int $branchId,
        AssistantConversation $conversation,
    ): void {
        abort_unless((int) $conversation->branch_id === (int) $branchId, 404);
        abort_unless((int) $conversation->user_id === $userId, 403);
    }
}
