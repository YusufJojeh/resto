<?php

namespace Tests\Feature\RestoCafe;

use App\Enums\SubscriptionStatus;
use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;

class BranchSubscriptionManagementTest extends RestoCafeTestCase
{
    public function test_admin_can_update_subscription_status(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'suspended',
            ])
            ->assertRedirect(route('branch.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('branches', [
            'id' => 1,
            'subscription_status' => 'suspended',
        ]);
    }

    public function test_admin_can_update_subscription_dates(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);
        $admin = $this->admin();

        $trialEnds = now()->addDays(14)->toISOString();
        $subscriptionEnds = now()->addMonths(1)->toISOString();

        $this->actingAs($admin)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'trialing',
                'trial_ends_at' => $trialEnds,
                'subscription_ends_at' => $subscriptionEnds,
            ])
            ->assertRedirect(route('branch.edit'));

        $branch = Branch::query()->find(1);
        $this->assertEquals(SubscriptionStatus::Trialing, $branch->subscription_status);
        $this->assertNotNull($branch->trial_ends_at);
        $this->assertNotNull($branch->subscription_ends_at);
    }

    public function test_manager_cannot_update_subscription_status(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);
        $manager = $this->manager();

        $this->actingAs($manager)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'suspended',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('branches', [
            'id' => 1,
            'subscription_status' => 'active',
        ]);
    }

    public function test_waiter_cannot_update_subscription_status(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);
        $waiter = $this->waiter();

        $this->actingAs($waiter)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'suspended',
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_update_subscription(): void
    {
        $this->patch(route('branch.subscription.update'), [
            'subscription_status' => 'suspended',
        ])
        ->assertRedirect(route('login'));
    }

    public function test_invalid_subscription_status_is_rejected(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'invalid_status',
            ])
            ->assertSessionHasErrors('subscription_status');

        $this->assertDatabaseHas('branches', [
            'id' => 1,
            'subscription_status' => 'active',
        ]);
    }

    public function test_subscription_status_change_immediately_affects_access(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);
        $admin = $this->admin();

        // Verify access is granted first
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        // Suspend the subscription
        $this->actingAs($admin)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'suspended',
            ]);

        // Verify access is now blocked
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.notice'));
    }

    public function test_users_from_another_branch_cannot_update_subscription(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);
        $admin = $this->admin();

        // Create secondary branch
        $secondary = $this->makeSecondaryBranch();
        $secondaryAdmin = $secondary['users']['admin'];

        // Try to update primary branch subscription (should still work because they use same branch_id in test)
        // This test verifies the branch isolation in the controller
        $this->actingAs($secondaryAdmin)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'suspended',
            ])
            ->assertRedirect(route('branch.edit'));

        // The secondary admin updated their own branch, not the primary
        $this->assertDatabaseHas('branches', [
            'id' => $secondary['branch']->id,
            'subscription_status' => 'suspended',
        ]);

        // Primary branch should be unchanged
        $this->assertDatabaseHas('branches', [
            'id' => 1,
            'subscription_status' => 'active',
        ]);
    }

    public function test_subscription_change_is_audited(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'suspended',
            ]);

        $this->assertDatabaseHas('branch_subscription_changes', [
            'branch_id' => 1,
            'actor_id' => $admin->id,
            'old_status' => 'active',
            'new_status' => 'suspended',
        ]);
    }

    public function test_admin_manual_assign_plan_without_provider_price_is_allowed(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);

        /** @phpstan-ignore-next-line */
        $manual = Plan::factory()->create([
            'slug' => 'tier-manual-assign-only',
            'is_active' => true,
            'provider_price_id' => null,
        ]);

        $this->actingAs($this->admin())
            /** @phpstan-ignore-next-line */
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'active',
                'plan_id' => $manual->id,
            ])
            ->assertRedirect(route('branch.edit'));

        /** @phpstan-ignore-next-line */
        $branch = Branch::query()->find(1);
        $this->assertSame($manual->id, (int) $branch->plan_id);
    }

    public function test_admin_manual_assign_rejects_inactive_plan_for_new_attachment(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);

        /** @phpstan-ignore-next-line */
        $inactive = Plan::factory()->inactive()->create(['slug' => 'sunset-tier-blocked']);

        $this->actingAs($this->admin())
            ->from(route('branch.edit'))
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => 'active',
                /** @phpstan-ignore-next-line */
                'plan_id' => $inactive->id,
            ])
            /** @phpstan-ignore-next-line */
            ->assertSessionHasErrors(['plan_id']);

        $this->assertDatabaseHas('branches', [
            'id' => 1,
            'plan_id' => null,
        ]);
    }

    private function setBranchSubscription(SubscriptionStatus $status): void
    {
        Branch::query()->whereKey(1)->update([
            'subscription_plan' => 'starter',
            'subscription_status' => $status->value,
            'is_active' => true,
        ]);
    }
}