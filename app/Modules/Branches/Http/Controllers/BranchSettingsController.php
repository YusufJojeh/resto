<?php

declare(strict_types=1);

namespace App\Modules\Branches\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Modules\Branches\Actions\UpdateBrandingAssets;
use App\Modules\Branches\Actions\UpdateBranchSubscription;
use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;
use App\Modules\Branches\Requests\UpdateBranchRequest;
use App\Modules\Branches\Requests\UpdateBranchSubscriptionRequest;
use App\Modules\Public\Support\BrandTokens;
use App\Support\Billing\BillingConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $branch = Branch::query()->with('plan')->findOrFail($request->user()->branch_id);

        /** @phpstan-ignore-next-line */
        $presentationBranch = $this->sanitizeBranchSecrets($branch, $request);

        $displayPlans = Plan::query()
            ->active()
            /** @phpstan-ignore-next-line */
            ->orderBy('sort_order')
            /** @phpstan-ignore-next-line */
            ->orderBy('name')
            /** @phpstan-ignore-next-line */
            ->get(['id', 'name', 'slug']);

        /** @phpstan-ignore-next-line */
        $displayPlansPayload = $displayPlans->map(static fn ($p): array => [
            /** @phpstan-ignore-next-line */
            'id' => $p->id,
            /** @phpstan-ignore-next-line */
            'name' => $p->name,
            /** @phpstan-ignore-next-line */
            'slug' => $p->slug,
        /** @phpstan-ignore-next-line */
        ])->values()->all();

        $currentPlanModel = $branch->plan;
        $currentPlanPayload = $currentPlanModel instanceof Plan ? [
            'id' => $currentPlanModel->id,
            'name' => $currentPlanModel->name,
            'slug' => $currentPlanModel->slug,
            'is_active' => (bool) $currentPlanModel->is_active,
        ] : null;

        /** @phpstan-ignore-next-line */
        $isAdmin = (bool) $request->user()?->hasRole(UserRole::Admin);

        if (! $isAdmin) {
            return Inertia::render('branches/edit', [
                'branch' => $presentationBranch,
                /** @phpstan-ignore-next-line */
                'branding' => BrandTokens::fromBranch($branch),
                'display_plans' => $displayPlansPayload,
                'current_plan' => $currentPlanPayload,
                'plans_for_assignment' => null,
                'billing_plans' => null,
                'billing_configured' => false,
                'can_start_checkout' => false,
                'can_open_billing_portal' => false,
                'billing' => null,
            ]);
        }

        /** @phpstan-ignore-next-line */
        $branchPlanKey = $branch->plan_id;

        /** @phpstan-ignore-next-line */
        $plansForAssignment = Plan::query()
            ->assignableForBranch(is_numeric($branchPlanKey) ? (int) $branchPlanKey : null)
            /** @phpstan-ignore-next-line */
            ->orderBy('sort_order')
            /** @phpstan-ignore-next-line */
            ->orderBy('name')
            /** @phpstan-ignore-next-line */
            ->get(['id', 'name', 'slug', 'is_active', 'provider_price_id'])
            ->map(static fn (Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'is_active' => (bool) $plan->is_active,
                'has_provider_price' => filled((string) $plan->provider_price_id),
            ])
            /** @phpstan-ignore-next-line */
            ->values()
            /** @phpstan-ignore-next-line */
            ->all();

        $billingPurchasable = Plan::query()
            ->purchasable()
            /** @phpstan-ignore-next-line */
            ->orderBy('sort_order')
            /** @phpstan-ignore-next-line */
            ->orderBy('name')
            /** @phpstan-ignore-next-line */
            ->get(['id', 'name', 'slug', 'provider_price_id']);

        /** @phpstan-ignore-next-line */
        $billingPlansPayload = $billingPurchasable->map(static fn (Plan $plan): array => [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'provider_price_id' => $plan->provider_price_id,
        ])
            /** @phpstan-ignore-next-line */
            ->values()
            /** @phpstan-ignore-next-line */
            ->all();

        $billingConfigured = BillingConfiguration::checkoutAvailable();
        /** @phpstan-ignore-next-line */
        $stripeCustomerConfigured = filled((string) $branch->provider_customer_id);

        /** @phpstan-ignore-next-line */
        return Inertia::render('branches/edit', [
            'branch' => $presentationBranch,
            /** @phpstan-ignore-next-line */
            'branding' => BrandTokens::fromBranch($branch),
            'display_plans' => $displayPlansPayload,
            'current_plan' => $currentPlanPayload,
            'plans_for_assignment' => $plansForAssignment,
            'billing_configured' => $billingConfigured,
            'billing_plans' => $billingPlansPayload,
            'can_start_checkout' => $billingConfigured && count($billingPlansPayload) > 0,
            'can_open_billing_portal' => BillingConfiguration::billingPortalAvailable()
                && $stripeCustomerConfigured,
            'billing' => [
                'state' => [
                    'explicitly_enabled' => BillingConfiguration::isExplicitlyEnabled(),
                    'checkout_ready' => $billingConfigured,
                    'stripe_secret_configured' => BillingConfiguration::stripeSecretConfigured(),
                    'webhook_configured' => BillingConfiguration::webhookAvailable(),
                    'portal_ready' => BillingConfiguration::billingPortalAvailable(),
                    'branch_has_customer' => $stripeCustomerConfigured,
                ],
            ],
        ]);
    }

    public function update(UpdateBranchRequest $request, UpdateBrandingAssets $assets): RedirectResponse
    {
        /** @phpstan-ignore-next-line */
        $branch = Branch::query()->findOrFail($request->user()->branch_id);

        $assets->handle(
            $branch,
            $request->file('logo'),
            $request->file('cover'),
        );

        $branch->update(array_merge(
            $request->validated(),
            array_filter([
                'logo_path' => $branch->logo_path,
                'cover_path' => $branch->cover_path,
            ], fn ($v) => $v !== null),
        ));

        return to_route('branch.edit')->with('success', 'Branch settings updated.');
    }

    public function updateSubscription(UpdateBranchSubscriptionRequest $request, UpdateBranchSubscription $updateSubscription): RedirectResponse
    {
        /** @phpstan-ignore-next-line */
        $branch = Branch::query()->findOrFail($request->user()->branch_id);

        $updateSubscription->handle(
            $branch,
            $request->validated(),
            /** @phpstan-ignore-next-line */
            $request->user()->id,
        );

        return to_route('branch.edit')->with('success', 'Subscription status updated.');
    }

    /** @phpstan-ignore-next-line */
    private function sanitizeBranchSecrets(Branch $branch, Request $request): Branch
    {
        /** @phpstan-ignore-next-line */
        if ($request->user()?->hasRole(UserRole::Admin)) {
            return $branch;
        }

        $masked = Branch::hydrate([(array) $branch->getAttributes()])->first();

        if (! $masked instanceof Branch) {
            return $branch;
        }

        $masked->makeHidden([
            'provider_name',
            'provider_customer_id',
            'provider_subscription_id',
        ]);

        /** @phpstan-ignore-next-line */
        if ($branch->relationLoaded('plan')) {
            /** @phpstan-ignore-next-line */
            $masked->setRelation('plan', $branch->plan);
        }

        return $masked;
    }
}
