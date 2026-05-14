<?php

declare(strict_types=1);

namespace Tests\Feature\RestoCafe;

use App\Enums\SubscriptionStatus;
use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;
use Inertia\Testing\AssertableInertia;

final class BranchPlanCatalogExposureTest extends RestoCafeTestCase
{
    public function test_admin_separates_assignment_options_billing_catalog_and_display_tiers(): void
    {
        $this->withoutVite();

        $inactiveCurrent = Plan::factory()->inactive()->create(['slug' => 'legacy-tier-assign']);
        /** @phpstan-ignore-next-line */
        $withPrice = Plan::factory()->create([
            'slug' => 'public-sell-tier',
            'is_active' => true,
            'provider_price_id' => 'price_exposure_a',
            'sort_order' => 10,
        ]);
        /** @phpstan-ignore-next-line */
        Plan::factory()->create([
            'slug' => 'internal-no-stripe-price',
            'is_active' => true,
            'provider_price_id' => null,
            'sort_order' => 20,
        ]);

        Branch::query()->whereKey(1)->update([
            /** @phpstan-ignore-next-line */
            'subscription_status' => SubscriptionStatus::Active->value,
            /** @phpstan-ignore-next-line */
            'plan_id' => $inactiveCurrent->id,
        ]);

        $this->actingAs($this->admin())
            /** @phpstan-ignore-next-line */
            ->get(route('branch.edit'))
            ->assertOk()
            /** @phpstan-ignore-next-line */
            ->assertInertia(function (AssertableInertia $page) use ($inactiveCurrent, $withPrice): void {
                $page->component('branches/edit')
                    ->where('billing_plans.0.id', $withPrice->id)
                    ->where('billing_plans.0.provider_price_id', 'price_exposure_a')
                    /** @phpstan-ignore-next-line */
                    ->where('current_plan.id', $inactiveCurrent->id)
                    ->where('current_plan.is_active', false)
                    ->where('plans_for_assignment', static function ($rows): bool {
                        $items = collect($rows)->values();

                        if ($items->count() < 3) {
                            return false;
                        }

                        $slugs = $items->pluck('slug')->all();

                        if (
                            ! in_array('legacy-tier-assign', $slugs, true)
                            || ! in_array('internal-no-stripe-price', $slugs, true)
                            || ! in_array('public-sell-tier', $slugs, true)
                        ) {
                            return false;
                        }

                        $internal = $items->firstWhere('slug', 'internal-no-stripe-price');

                        return $internal !== null
                            /** @phpstan-ignore-next-line */
                            && isset($internal['has_provider_price'])
                            && $internal['has_provider_price'] === false;
                    })
                    ->where('display_plans', static function ($rows): bool {
                        $items = collect($rows)->values();

                        if ($items->count() !== 2) {
                            return false;
                        }

                        /** @phpstan-ignore-next-line */
                        $slugs = $items->pluck('slug')->all();

                        return in_array('public-sell-tier', $slugs, true)
                            && in_array('internal-no-stripe-price', $slugs, true)
                            && ! in_array('legacy-tier-assign', $slugs, true);
                    });
            });
    }

    public function test_manager_subscription_payload_hides_operator_billing_structures(): void
    {
        $this->withoutVite();

        Plan::factory()->create(['slug' => 'tier-manager-view', 'is_active' => true]);

        Branch::query()->whereKey(1)->update([
            /** @phpstan-ignore-next-line */
            'subscription_status' => SubscriptionStatus::Active->value,
            /** @phpstan-ignore-next-line */
            'plan_id' => null,
        ]);

        $this->actingAs($this->manager())
            /** @phpstan-ignore-next-line */
            ->get(route('branch.edit'))
            ->assertOk()
            /** @phpstan-ignore-next-line */
            ->assertInertia(function (AssertableInertia $page): void {
                $page->component('branches/edit')
                    ->where('plans_for_assignment', null)
                    ->where('billing_plans', null)
                    ->where('billing', null)
                    ->where('billing_configured', false)
                    ->where('can_start_checkout', false)
                    ->where('can_open_billing_portal', false);
            });
    }
}
