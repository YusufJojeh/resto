<?php

namespace Tests\Feature\RestoCafe;

use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Support\Subscription\SubscriptionAccessReason;

class SubscriptionLifecycleRulesTest extends RestoCafeTestCase
{
    protected function setBranch(array $payload): void
    {
        Branch::query()->whereKey(1)->update(array_merge([
            'subscription_plan' => 'starter',
            'is_active' => true,
            'plan_id' => null,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'current_period_ends_at' => null,
        ], $payload));
    }

    public function test_trialing_without_trial_date_allows_access(): void
    {
        $this->setBranch([
            'subscription_status' => SubscriptionStatus::Trialing->value,
            'trial_ends_at' => null,
        ]);

        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk();
        $this->assertSame(SubscriptionAccessReason::TRIAL_ACTIVE, Branch::find(1)->subscriptionAccessReason());
    }

    public function test_past_due_inside_grace_allows_access(): void
    {
        config(['subscriptions.grace_days' => 5]);

        $this->travelTo(\Carbon\Carbon::parse('2026-06-01 12:00:00'));

        $this->setBranch([
            'subscription_status' => SubscriptionStatus::PastDue->value,
            'subscription_ends_at' => \Carbon\Carbon::parse('2026-05-29 09:00:00'),
            'current_period_ends_at' => null,
        ]);

        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk();
        $this->assertSame(SubscriptionAccessReason::PAST_DUE_GRACE, Branch::find(1)->subscriptionAccessReason());
    }

    public function test_past_due_after_grace_blocks_access(): void
    {
        config(['subscriptions.grace_days' => 2]);

        $this->travelTo(\Carbon\Carbon::parse('2026-06-10 15:00:00'));

        $this->setBranch([
            'subscription_status' => SubscriptionStatus::PastDue->value,
            'subscription_ends_at' => \Carbon\Carbon::parse('2026-06-02 09:00:00'),
            'current_period_ends_at' => null,
        ]);

        // Grace boundary: 02 June + 2 days → 04 June 09:00; 10 June is expired.
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.notice'));
        $this->assertSame(SubscriptionAccessReason::PAST_DUE_EXPIRED, Branch::find(1)->subscriptionAccessReason());
    }

    public function test_canceled_allows_until_period_boundary(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-05-05 09:00:00'));

        $this->setBranch([
            'subscription_status' => SubscriptionStatus::Canceled->value,
            'current_period_ends_at' => \Carbon\Carbon::parse('2026-05-20 23:59:59'),
            'subscription_ends_at' => null,
        ]);

        $this->actingAs($this->manager())->get(route('dashboard'))->assertOk();
        $this->assertSame(SubscriptionAccessReason::CANCELED_UNTIL_PERIOD_END, Branch::find(1)->subscriptionAccessReason());
    }

    public function test_canceled_after_period_blocks_when_only_subscription_end_exists(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-06-01 09:00:00'));

        $this->setBranch([
            'subscription_status' => SubscriptionStatus::Canceled->value,
            'current_period_ends_at' => null,
            'subscription_ends_at' => \Carbon\Carbon::parse('2026-05-15 00:00:00'),
        ]);

        $this->actingAs($this->admin())
            ->get(route('orders.index'))
            ->assertRedirect(route('subscription.notice'));
        $this->assertSame(SubscriptionAccessReason::CANCELED_EXPIRED, Branch::find(1)->subscriptionAccessReason());
    }

    public function test_json_requests_include_reason_codes(): void
    {
        $this->setBranch([
            'subscription_status' => SubscriptionStatus::Suspended->value,
        ]);

        $this->actingAs($this->admin())
            ->getJson(route('dashboard'))
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Subscription access required.',
                'reason_code' => SubscriptionAccessReason::SUSPENDED,
            ])
            ->assertJsonStructure(['message', 'reason_code', 'reason']);
    }

    public function test_subscription_notice_avoids_redirect_loops_under_lifecycle_denial(): void
    {
        $this->withoutVite();
        $this->travelTo(now()->seconds(0));
        $this->setBranch([
            'subscription_status' => SubscriptionStatus::Suspended->value,
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('subscription.notice'))->assertOk();
        $this->actingAs($admin)->get(route('subscription.notice'))->assertOk();
    }

    public function test_admin_can_update_lifecycle_fields_including_cancel_state(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-05-06 09:00:00'));

        $this->setBranch([
            'subscription_status' => SubscriptionStatus::Active->value,
            'trial_ends_at' => null,
            'subscription_ends_at' => now()->addMonth(),
            'current_period_ends_at' => null,
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => SubscriptionStatus::Canceled->value,
                'trial_ends_at' => null,
                'subscription_ends_at' => null,
                'current_period_ends_at' => now()->addWeeks(2)->toISOString(),
            ])
            ->assertRedirect(route('branch.edit'));

        $branch = Branch::find(1);
        $this->assertSame(SubscriptionStatus::Canceled, $branch->subscription_status);
        $this->assertNotNull($branch->current_period_ends_at);
        $this->assertTrue($branch->hasSubscriptionAccess());
    }

    public function test_audit_records_access_outcomes_when_columns_exist(): void
    {
        $this->setBranch([
            'subscription_status' => SubscriptionStatus::Active->value,
        ]);

        $admin = User::where('email', 'admin@restocafe.test')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => SubscriptionStatus::Suspended->value,
            ]);

        $this->assertDatabaseHas('branch_subscription_changes', [
            'branch_id' => 1,
            'actor_id' => $admin->id,
            'old_status' => 'active',
            'new_status' => 'suspended',
            'new_access_allowed' => false,
            'new_access_reason' => SubscriptionAccessReason::SUSPENDED,
            'old_access_allowed' => true,
            'old_access_reason' => SubscriptionAccessReason::ACTIVE,
        ]);
    }

    public function test_waiter_cannot_update_lifecycle(): void
    {
        $this->setBranch([
            'subscription_status' => SubscriptionStatus::Active->value,
        ]);

        $this->actingAs($this->waiter())
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => SubscriptionStatus::Suspended->value,
            ])
            ->assertForbidden();

        $this->assertSame(SubscriptionStatus::Active, Branch::query()->findOrFail(1)->subscription_status);
    }
}
