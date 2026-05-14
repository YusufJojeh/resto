<?php

namespace Tests\Feature\RestoCafe;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;
use App\Support\Subscription\PlanFeatureKey;
use App\Support\Subscription\PlanLimitKey;

class PlanAndEntitlementsTest extends RestoCafeTestCase
{
    public function test_admin_can_create_a_subscription_plan(): void
    {
        $features = [];
        foreach (PlanFeatureKey::all() as $key) {
            $features[$key] = true;
        }

        $this->actingAs($this->admin())
            ->post(route('plans.store'), [
                'name' => 'Enterprise',
                'slug' => 'enterprise-test',
                'description' => 'Internal tier',
                'price_amount' => null,
                'billing_interval' => null,
                'is_active' => true,
                'sort_order' => 1,
                'features' => $features,
                'limits' => [],
            ])
            ->assertRedirect(route('plans.index'));

        $this->assertDatabaseHas('plans', [
            'slug' => 'enterprise-test',
            'is_active' => true,
        ]);
    }

    public function test_manager_cannot_manage_subscription_plans(): void
    {
        $this->actingAs($this->manager())
            ->get(route('plans.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_subscription_plan(): void
    {
        $plan = Plan::factory()->create(['name' => 'Basic', 'slug' => 'basic-test']);
        $features = [];
        foreach (PlanFeatureKey::all() as $key) {
            $features[$key] = $key !== PlanFeatureKey::ADVANCED_ANALYTICS;
        }

        $this->actingAs($this->admin())
            ->put(route('plans.update', $plan), [
                'name' => 'Basic Plus',
                'slug' => 'basic-test',
                'description' => 'Updated note',
                'price_amount' => null,
                'billing_interval' => null,
                'is_active' => true,
                'sort_order' => 2,
                'features' => $features,
                'limits' => [PlanLimitKey::MAX_USERS => 12],
            ])
            ->assertRedirect(route('plans.index'));

        $plan->refresh();
        $this->assertSame('Basic Plus', $plan->name);
        $this->assertFalse($plan->features[PlanFeatureKey::ADVANCED_ANALYTICS]);
    }

    public function test_admin_can_assign_plan_to_branch(): void
    {
        Branch::query()->whereKey(1)->update([
            'subscription_plan' => 'starter',
            'subscription_status' => SubscriptionStatus::Active->value,
            'plan_id' => null,
        ]);

        $plan = Plan::factory()->create(['slug' => 'assigned-tier']);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => SubscriptionStatus::Active->value,
                'plan_id' => $plan->id,
                'trial_ends_at' => null,
                'subscription_ends_at' => null,
            ])
            ->assertRedirect(route('branch.edit'));

        $this->assertDatabaseHas('branches', [
            'id' => 1,
            'plan_id' => $plan->id,
            'subscription_plan' => $plan->slug,
        ]);

        $this->assertDatabaseHas('branch_subscription_changes', [
            'branch_id' => 1,
            'actor_id' => $admin->id,
            'new_plan_id' => $plan->id,
            'new_status' => SubscriptionStatus::Active->value,
        ]);
    }

    public function test_inactive_plan_cannot_be_assigned_to_branch_without_existing_link(): void
    {
        $inactive = Plan::factory()->inactive()->create(['slug' => 'paused-tier']);

        Branch::query()->whereKey(1)->update([
            'plan_id' => null,
            'subscription_status' => SubscriptionStatus::Active->value,
            'subscription_plan' => 'starter',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->from(route('branch.edit'))
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => SubscriptionStatus::Active->value,
                'plan_id' => $inactive->id,
            ])
            ->assertSessionHasErrors('plan_id');
    }

    public function test_branch_can_retain_existing_inactive_plan_assignment(): void
    {
        $inactive = Plan::factory()->inactive()->create(['slug' => 'historic-tier']);

        Branch::query()->whereKey(1)->update([
            'plan_id' => $inactive->id,
            'subscription_plan' => $inactive->slug,
            'subscription_status' => SubscriptionStatus::Active->value,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => SubscriptionStatus::Active->value,
                'plan_id' => $inactive->id,
            ])
            ->assertRedirect(route('branch.edit'));
    }

    public function test_kitchen_routes_blocked_when_plan_excludes_kitchen_feature(): void
    {
        $features = [];
        foreach (PlanFeatureKey::all() as $key) {
            $features[$key] = true;
        }
        $features[PlanFeatureKey::KITCHEN] = false;

        $plan = Plan::factory()->create(['features' => $features]);

        Branch::query()->whereKey(1)->update([
            'plan_id' => $plan->id,
            'subscription_status' => SubscriptionStatus::Active->value,
            'is_active' => true,
        ]);

        $this->actingAs($this->kitchen())
            ->get(route('kitchen.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_reports_blocked_when_plan_excludes_reports_feature(): void
    {
        $features = [];
        foreach (PlanFeatureKey::all() as $key) {
            $features[$key] = true;
        }
        $features[PlanFeatureKey::REPORTS] = false;

        $plan = Plan::factory()->create(['features' => $features]);

        Branch::query()->whereKey(1)->update([
            'plan_id' => $plan->id,
            'subscription_status' => SubscriptionStatus::Active->value,
            'is_active' => true,
        ]);

        $this->actingAs($this->manager())
            ->get(route('reports.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_plan_user_limit_blocks_new_staff(): void
    {
        $activeUsersCount = User::query()->where('branch_id', 1)->where('is_active', true)->count();

        $plan = Plan::factory()->withLimits([PlanLimitKey::MAX_USERS => $activeUsersCount])->create();

        Branch::query()->whereKey(1)->update([
            'plan_id' => $plan->id,
            'subscription_status' => SubscriptionStatus::Active->value,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'Extra Host',
                'email' => 'extra-host@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => UserRole::Waiter->value,
            ])
            ->assertSessionHas('error');
    }

    public function test_branch_without_plan_retains_legacy_feature_access(): void
    {
        Branch::query()->whereKey(1)->update([
            'plan_id' => null,
            'subscription_status' => SubscriptionStatus::Active->value,
            'is_active' => true,
        ]);

        $this->actingAs($this->kitchen())->get(route('kitchen.index'))->assertOk();

        $this->actingAs($this->manager())->get(route('reports.index'))->assertOk();
    }

    public function test_plan_delete_is_blocked_when_branches_reference_plan(): void
    {
        $plan = Plan::factory()->create();
        Branch::query()->whereKey(1)->update(['plan_id' => $plan->id]);

        $this->actingAs($this->admin())
            ->from(route('plans.index'))
            ->delete(route('plans.destroy', $plan))
            ->assertSessionHas('error');
    }

    public function test_secondary_branch_admin_cannot_affect_primary_branch_plan(): void
    {
        $plan = Plan::factory()->create();
        $secondary = $this->makeSecondaryBranch();

        Branch::query()->whereKey(1)->update(['plan_id' => null]);

        $this->actingAs($secondary['users']['admin'])
            ->patch(route('branch.subscription.update'), [
                'subscription_status' => SubscriptionStatus::Active->value,
                'plan_id' => $plan->id,
                'trial_ends_at' => null,
                'subscription_ends_at' => null,
            ])
            ->assertRedirect(route('branch.edit'));

        $this->assertDatabaseHas('branches', ['id' => $secondary['branch']->id, 'plan_id' => $plan->id]);
        $this->assertDatabaseHas('branches', ['id' => 1, 'plan_id' => null]);
    }
}
