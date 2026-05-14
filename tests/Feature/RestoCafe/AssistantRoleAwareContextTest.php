<?php

namespace Tests\Feature\RestoCafe;

use App\Modules\Assistant\Models\AssistantConversation;
use App\Modules\Assistant\Support\AssistantContextBuilder;
use App\Modules\Assistant\Support\AssistantIntent;

class AssistantRoleAwareContextTest extends RestoCafeTestCase
{
    public function test_admin_gets_users_context(): void
    {
        $user = $this->admin();
        $conversation = AssistantConversation::query()->create([
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'title' => 'admin',
        ]);

        $context = app(AssistantContextBuilder::class)->build(
            $user,
            $conversation,
            AssistantIntent::USERS,
            'Show staff summary',
            '/users',
        );

        $this->assertArrayHasKey('total_users', $context['allowed_context']['primary']);
        $this->assertSame([], $context['denied_context']);
    }

    public function test_manager_gets_reports_context(): void
    {
        $user = $this->manager();
        $conversation = AssistantConversation::query()->create([
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'title' => 'manager',
        ]);

        $context = app(AssistantContextBuilder::class)->build(
            $user,
            $conversation,
            AssistantIntent::REVENUE,
            'Summarize revenue',
            '/reports',
        );

        $this->assertArrayHasKey('metrics', $context['allowed_context']['primary']);
        $this->assertSame([], $context['denied_context']);
    }

    public function test_waiter_is_denied_reports_context(): void
    {
        $user = $this->waiter();
        $conversation = AssistantConversation::query()->create([
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'title' => 'waiter',
        ]);

        $context = app(AssistantContextBuilder::class)->build(
            $user,
            $conversation,
            AssistantIntent::REVENUE,
            'Show revenue',
            '/dashboard',
        );

        $this->assertNotEmpty($context['denied_context']);
        $this->assertArrayHasKey('stats', $context['allowed_context']['primary']);
    }

    public function test_cashier_gets_invoice_context(): void
    {
        $user = $this->cashier();
        $conversation = AssistantConversation::query()->create([
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'title' => 'cashier',
        ]);

        $context = app(AssistantContextBuilder::class)->build(
            $user,
            $conversation,
            AssistantIntent::INVOICES,
            'Invoice status',
            '/invoices',
        );

        $this->assertArrayHasKey('metrics', $context['allowed_context']['primary']);
    }

    public function test_kitchen_gets_kitchen_context(): void
    {
        $user = $this->kitchen();
        $conversation = AssistantConversation::query()->create([
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'title' => 'kitchen',
        ]);

        $context = app(AssistantContextBuilder::class)->build(
            $user,
            $conversation,
            AssistantIntent::KITCHEN,
            'Kitchen queue',
            '/kitchen',
        );

        $this->assertArrayHasKey('queue_count', $context['allowed_context']['primary']);
    }
}
