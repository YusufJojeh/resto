<?php

namespace Tests\Feature\RestoCafe;

use App\Enums\SubscriptionStatus;
use App\Modules\Branches\Models\Branch;
use App\Support\Subscription\SubscriptionAccessReason;

class SubscriptionAccessTest extends RestoCafeTestCase
{
    public function test_active_branch_users_can_access_operational_routes(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Active);

        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk();
        $this->actingAs($this->admin())->get(route('branch.edit'))->assertOk();
        $this->actingAs($this->admin())->get(route('users.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('messages.index'))->assertOk();
    }

    public function test_suspended_branch_users_are_redirected_to_subscription_notice(): void
    {
        $this->withoutVite();
        $this->setBranchSubscription(SubscriptionStatus::Suspended);

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.notice'));

        // Managers/admins keep branch settings (subscription + Stripe billing tooling) reachable during blocks.
        $this->actingAs($this->admin())
            ->get(route('branch.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('branches/edit'));

        $this->actingAs($this->manager())
            ->get(route('branch.edit'))
            ->assertOk();

        $this->actingAs($this->waiter())
            ->get(route('branch.edit'))
            ->assertRedirect(route('subscription.notice'));
    }

    public function test_expired_branch_users_are_redirected_to_subscription_notice(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Expired);

        $this->actingAs($this->manager())
            ->get(route('inventory.index'))
            ->assertRedirect(route('subscription.notice'));
    }

    public function test_future_trialing_branch_can_access_operational_routes(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Trialing, trialEndsAt: now()->addDay());

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_past_trialing_branch_is_redirected_to_subscription_notice(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Trialing, trialEndsAt: now()->subMinute());

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.notice'));
    }

    public function test_blocked_users_can_still_access_allowed_account_routes(): void
    {
        $this->withoutVite();
        $this->setBranchSubscription(SubscriptionStatus::Suspended);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('subscription.notice'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('subscription/notice'));

        $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
        $this->actingAs($admin)->get(route('password.edit'))->assertOk();
        $this->actingAs($admin)->get(route('appearance'))->assertOk();
        $this->actingAs($admin)->post(route('logout'))->assertRedirect('/');
    }

    public function test_guests_are_redirected_to_login_before_subscription_gate(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_subscription_notice_route_never_redirects_to_itself(): void
    {
        $this->withoutVite();
        $this->setBranchSubscription(SubscriptionStatus::Suspended);

        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('subscription.notice'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('subscription/notice'));

        $response = $this->actingAs($admin)->get(route('subscription.notice'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('subscription/notice'));

        $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
    }

    public function test_json_requests_get_json_response_not_html_redirect(): void
    {
        $this->setBranchSubscription(SubscriptionStatus::Suspended);

        $admin = $this->admin();

        // Test with getJson on a web route - returns 403 with JSON body
        $this->actingAs($admin)
            ->getJson(route('dashboard'))
            ->assertStatus(403)
            ->assertJsonStructure(['message', 'reason_code', 'reason'])
            ->assertJson([
                'message' => 'Subscription access required.',
                'reason_code' => SubscriptionAccessReason::SUSPENDED,
            ]);
    }

    public function test_inactive_branch_users_are_blocked_regardless_of_subscription(): void
    {
        Branch::query()->whereKey(1)->update([
            'subscription_status' => SubscriptionStatus::Active->value,
            'is_active' => false,
        ]);

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.notice'));
    }

    public function test_json_payload_includes_inactive_branch_reason_code(): void
    {
        Branch::query()->whereKey(1)->update([
            'subscription_status' => SubscriptionStatus::Active->value,
            'is_active' => false,
        ]);

        $this->actingAs($this->admin())
            ->getJson(route('dashboard'))
            ->assertStatus(403)
            ->assertJsonPath('reason_code', SubscriptionAccessReason::BRANCH_INACTIVE);
    }

    public function test_branch_isolation_prevents_viewing_other_branch_subscription(): void
    {
        $this->withoutVite();
        $this->setBranchSubscription(SubscriptionStatus::Active);

        $admin = $this->admin();

        // Create secondary branch with active subscription so they can access branch.edit
        $secondary = $this->makeSecondaryBranch();
        $secondary['branch']->update([
            'subscription_status' => SubscriptionStatus::Active->value,
            'is_active' => true,
        ]);

        // Primary admin should see their branch
        $this->actingAs($admin)->get(route('branch.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('branch.id', 1));

        // Secondary branch admin should see their branch
        $secondaryAdmin = $secondary['users']['admin'];
        $this->actingAs($secondaryAdmin)->get(route('branch.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('branch.id', $secondary['branch']->id));
    }

    public function test_authorized_admin_can_view_subscription_summary_in_branch_edit(): void
    {
        $this->withoutVite();
        $this->setBranchSubscription(SubscriptionStatus::Active, trialEndsAt: now()->addDays(7), subscriptionEndsAt: now()->addMonths(1));

        $admin = $this->admin();
        $manager = $this->manager();

        $this->actingAs($admin)->get(route('branch.edit'))->assertOk();
        $this->actingAs($manager)->get(route('branch.edit'))->assertOk();
    }

    public function test_user_without_branch_gets_blocked(): void
    {
        $userWithoutBranch = \App\Models\User::query()->create([
            'name' => 'No Branch User',
            'email' => 'nobranch@restocafe.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
            'branch_id' => null,
        ]);
        $userWithoutBranch->syncRoles(['admin']);

        $this->actingAs($userWithoutBranch)
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.notice'));
    }

    private function setBranchSubscription(
        SubscriptionStatus $status,
        mixed $trialEndsAt = null,
        mixed $subscriptionEndsAt = null,
    ): void {
        Branch::query()->whereKey(1)->update([
            'subscription_plan' => 'starter',
            'subscription_status' => $status->value,
            'trial_ends_at' => $trialEndsAt,
            'subscription_ends_at' => $subscriptionEndsAt,
            'is_active' => true,
        ]);
    }
}