<?php

namespace Tests\Feature\RestoCafe;

use App\Enums\SubscriptionStatus;
use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;
use App\Support\Billing\Actions\ApplyProviderSubscriptionPatch;
use App\Support\Billing\Contracts\BillingProviderContract;
use App\Support\Billing\Contracts\StripeSubscriptionGatewayContract;
use App\Support\Billing\Data\ProviderSubscriptionPatch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Fakes\FakeStripeBillingProvider;

class BillingStripeTest extends RestoCafeTestCase
{
    protected function billingReadyCheckout(): FakeStripeBillingProvider
    {
        config([
            'billing.enabled' => true,
            'billing.provider' => 'stripe',
            'billing.stripe.secret_key' => 'sk_test_fixture',
            'billing.stripe.webhook_secret' => 'whsec_fixture',
            'billing.checkout.success_url' => 'https://example.com/success?session_id={CHECKOUT_SESSION_ID}',
            'billing.checkout.cancel_url' => 'https://example.com/cancel',
        ]);

        $fake = new FakeStripeBillingProvider();
        $this->swap(BillingProviderContract::class, $fake);
        $this->swap(StripeSubscriptionGatewayContract::class, $fake);

        return $fake;
    }

    private function stripeSignatureHeader(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return "t={$timestamp},v1={$signature}";
    }

    private function postStripeWebhook(string $payload, string $signature): \Illuminate\Testing\TestResponse
    {
        return $this->call(
            'POST',
            route('billing.stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );
    }

    public function test_waiter_forbidden_checkout(): void
    {
        $this->billingReadyCheckout();

        $plan = Plan::factory()->create(['is_active' => true, 'provider_price_id' => 'price_fixture']);

        $this->actingAs($this->waiter())
            ->postJson(route('branch.billing.checkout'), ['plan_id' => $plan->id])
            ->assertForbidden();
    }

    public function test_admin_can_start_checkout_via_json(): void
    {
        $this->billingReadyCheckout();

        $plan = Plan::factory()->create(['is_active' => true, 'provider_price_id' => 'price_fixture']);

        $this->actingAs($this->admin())
            ->postJson(route('branch.billing.checkout'), ['plan_id' => $plan->id])
            ->assertOk()
            ->assertJsonPath('checkout_url', 'https://checkout.restocafe.test/session');
    }

    public function test_plan_without_provider_price_fails_validation(): void
    {
        $this->billingReadyCheckout();

        $plan = Plan::factory()->create(['is_active' => true, 'provider_price_id' => null]);

        $this->actingAs($this->admin())
            ->from(route('branch.edit'))
            ->post(route('branch.billing.checkout'), ['plan_id' => $plan->id])
            ->assertRedirect(route('branch.edit'))
            ->assertSessionHasErrors(['plan_id']);
    }

    public function test_plan_inactive_not_accepted_for_checkout(): void
    {
        $this->billingReadyCheckout();

        $inactive = Plan::factory()->inactive()->create(['provider_price_id' => 'price_x']);

        $this->actingAs($this->admin())
            ->from(route('branch.edit'))
            ->post(route('branch.billing.checkout'), ['plan_id' => $inactive->id])
            ->assertRedirect(route('branch.edit'))
            ->assertSessionHasErrors(['plan_id']);
    }

    public function test_checkout_fails_when_billing_not_enabled_even_with_keys(): void
    {
        config([
            'billing.enabled' => false,
            'billing.stripe.secret_key' => 'sk_test_fixture',
            'billing.checkout.success_url' => 'https://example.com/success',
            'billing.checkout.cancel_url' => 'https://example.com/cancel',
        ]);

        $fake = new FakeStripeBillingProvider();
        $this->swap(BillingProviderContract::class, $fake);
        $this->swap(StripeSubscriptionGatewayContract::class, $fake);

        $plan = Plan::factory()->create(['is_active' => true, 'provider_price_id' => 'price_fixture']);

        $this->actingAs($this->admin())
            ->from(route('branch.edit'))
            ->post(route('branch.billing.checkout'), ['plan_id' => $plan->id])
            ->assertRedirect(route('branch.edit'))
            ->assertSessionHasErrors(['billing']);
    }

    public function test_checkout_defensive_validation_json_when_plan_missing_stripe_price(): void
    {
        $this->billingReadyCheckout();

        $plan = Plan::factory()->create(['is_active' => true, 'provider_price_id' => null]);

        $this->actingAs($this->admin())
            ->postJson(route('branch.billing.checkout'), ['plan_id' => $plan->id])
            ->assertStatus(422)
            ->assertJsonPath('errors.plan_id.0', 'This plan does not have a Stripe price id configured.');
    }

    public function test_staff_blocked_from_customer_portal(): void
    {
        $this->billingReadyCheckout();

        Branch::query()->whereKey(1)->update(['provider_customer_id' => 'cus_fixture']);

        $this->actingAs($this->waiter())
            ->post(route('branch.billing.portal'))
            ->assertForbidden();
    }

    public function test_portal_fails_when_billing_disabled_even_with_customer(): void
    {
        config([
            'billing.enabled' => false,
            'billing.provider' => 'stripe',
            'billing.stripe.secret_key' => 'sk_test_fixture',
        ]);

        $fake = new FakeStripeBillingProvider();
        $this->swap(BillingProviderContract::class, $fake);
        $this->swap(StripeSubscriptionGatewayContract::class, $fake);

        Branch::query()->whereKey(1)->update(['provider_customer_id' => 'cus_fixture']);

        $this->actingAs($this->admin())
            ->from(route('branch.edit'))
            ->post(route('branch.billing.portal'))
            ->assertRedirect(route('branch.edit'))
            ->assertSessionHasErrors(['billing']);
    }

    public function test_portal_fails_when_no_provider_customer_attached(): void
    {
        $this->billingReadyCheckout();

        Branch::query()->whereKey(1)->update([
            /** @phpstan-ignore-next-line */
            'provider_customer_id' => null,
        ]);

        $this->actingAs($this->admin())
            ->from(route('branch.edit'))
            ->post(route('branch.billing.portal'))
            ->assertRedirect(route('branch.edit'))
            ->assertSessionHasErrors(['billing']);
    }

    public function test_admin_redirects_to_customer_portal_session(): void
    {
        $this->billingReadyCheckout();

        Branch::query()->whereKey(1)->update(['provider_customer_id' => 'cus_fixture']);

        $this->actingAs($this->admin())
            ->post(route('branch.billing.portal'))
            ->assertRedirect('https://billing-portal.restocafe.test/session');
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        config([
            'billing.enabled' => true,
            'billing.stripe.webhook_secret' => 'whsec_fixture',
        ]);

        $payload = json_encode([
            'id' => 'evt_fixture',
            'type' => 'ping',
            'data' => ['object' => []],
        ]);

        $this->call(
            'POST',
            route('billing.stripe.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't=999,v1=baddigest',
            ],
            /** @phpstan-ignore-next-line */
            is_string($payload) ? $payload : '{}'
        )->assertStatus(400);
    }

    public function test_webhook_duplicate_is_idempotent(): void
    {
        config([
            'billing.enabled' => true,
            'billing.stripe.webhook_secret' => 'whsec_fixture',
        ]);

        $payload = json_encode([
            'id' => 'evt_duplicate_fixture',
            'type' => 'ping',
            'livemode' => false,
            'data' => ['object' => []],
        ]);

        /** @phpstan-ignore-next-line */
        $signature = $this->stripeSignatureHeader(is_string($payload) ? $payload : '{}', 'whsec_fixture');

        foreach ([1, 2] as $_) {
            $this->postStripeWebhook(is_string($payload) ? $payload : '{}', $signature)->assertOk();
        }

        /** @phpstan-ignore-next-line */
        $count = DB::table('billing_webhook_events')
            /** @phpstan-ignore-next-line */
            ->where('provider_event_id', 'evt_duplicate_fixture')
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_apply_patch_links_via_trusted_hint_on_first_subscription(): void
    {
        Branch::query()->whereKey(1)->update([
            /** @phpstan-ignore-next-line */
            'subscription_status' => SubscriptionStatus::Expired->value,
            /** @phpstan-ignore-next-line */
            'trial_ends_at' => now()->subDay(),
            /** @phpstan-ignore-next-line */
            'subscription_ends_at' => now()->subDay(),
            /** @phpstan-ignore-next-line */
            'provider_subscription_id' => null,
        ]);

        /** @phpstan-ignore-next-line */
        $plan = Plan::factory()->create(['is_active' => true, 'provider_price_id' => 'price_linked']);

        $patch = new ProviderSubscriptionPatch(
            provider: 'stripe',
            providerEventId: 'evt_apply',
            eventType: 'checkout.session.completed',
            subscriptionStatus: SubscriptionStatus::Active,
            /** @phpstan-ignore-next-line */
            planId: $plan->id,
            trialEndsAt: CarbonImmutable::now()->addDays(7),
            currentPeriodEndsAt: CarbonImmutable::now()->addMonth(),
            subscriptionEndsAt: CarbonImmutable::now()->addMonth(),
            providerName: 'stripe',
            providerCustomerId: 'cus_fixture',
            providerSubscriptionId: 'sub_fixture',
            resolveBranchIdHint: 1,
            trustedBranchResolutionFromHintOnly: true,
        );

        $applied = resolve(ApplyProviderSubscriptionPatch::class)->execute($patch);

        $this->assertNotNull($applied);
        $this->assertDatabaseHas('branch_subscription_changes', [
            'branch_id' => 1,
            'source' => 'stripe',
            /** @phpstan-ignore-next-line */
            'provider_event_id' => 'evt_apply',
        ]);
    }

    public function test_existing_subscription_owner_wins_over_metadata_branch_hint(): void
    {
        Branch::query()->whereKey(1)->update([
            /** @phpstan-ignore-next-line */
            'subscription_status' => SubscriptionStatus::Active->value,
            'provider_subscription_id' => 'sub_real',
            'provider_customer_id' => 'cus_real',
        ]);

        $secondaryId = $this->makeSecondaryBranch()['branch']->id;

        $patch = new ProviderSubscriptionPatch(
            provider: 'stripe',
            providerEventId: 'evt_abuse',
            eventType: 'invoice.payment_succeeded',
            subscriptionStatus: SubscriptionStatus::Active,
            planId: null,
            trialEndsAt: null,
            currentPeriodEndsAt: null,
            subscriptionEndsAt: null,
            providerName: 'stripe',
            providerCustomerId: 'cus_bad',
            providerSubscriptionId: 'sub_real',
            resolveBranchIdHint: $secondaryId,
            trustedBranchResolutionFromHintOnly: true,
        );

        $branch = resolve(ApplyProviderSubscriptionPatch::class)->execute($patch);

        /** @phpstan-ignore-next-line */
        $this->assertNotNull($branch);
        $this->assertSame(1, (int) $branch->id);
    }
}
