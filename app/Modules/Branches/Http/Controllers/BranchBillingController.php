<?php

declare(strict_types=1);

namespace App\Modules\Branches\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;
use App\Modules\Branches\Requests\StartBranchBillingCheckoutRequest;
use App\Modules\Branches\Requests\StartBranchBillingPortalRequest;
use App\Support\Billing\Actions\CreateBillingPortalSession;
use App\Support\Billing\Actions\CreateCheckoutSession;
use App\Support\Billing\BillingConfiguration;
use App\Support\Billing\BillingProviderManager;
use App\Support\Billing\Contracts\StripeSubscriptionGatewayContract;
use App\Support\Billing\Data\CreateSubscriptionCheckoutInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BranchBillingController extends Controller
{
    public function checkout(
        StartBranchBillingCheckoutRequest $request,
        CreateCheckoutSession $createCheckoutSession,
        BillingProviderManager $billingProviders,
    ): Response|RedirectResponse {
        /** @phpstan-ignore-next-line */
        $branch = Branch::query()->findOrFail((int) $request->user()->branch_id);

        $plan = Plan::query()->findOrFail((int) $request->validated()['plan_id']);

        if (! BillingConfiguration::checkoutAvailable()) {
            return $this->billingFailureResponse($request, 'Billing checkout is not configured. Enable billing and set Stripe keys plus checkout URLs.');
        }

        if (! $plan->isPurchasableForStripeCheckout()) {
            $detail = ! $plan->is_active
                ? 'Inactive plans cannot be purchased through Stripe checkout.'
                : 'This plan does not have a Stripe price id configured.';

            return $this->billingFailureResponse($request, $detail, errorKey: 'plan_id');
        }

        try {
            $checkoutUrl = $createCheckoutSession->handle(
                $billingProviders->billingProvider(),
                new CreateSubscriptionCheckoutInput(
                    branchId: $branch->id,
                    planId: $plan->id,
                    /** @phpstan-ignore-next-line */
                    userId: (int) $request->user()->id,
                    stripePriceId: (string) $plan->provider_price_id,
                    successUrl: rtrim((string) config('billing.checkout.success_url'), '?&'),
                    cancelUrl: rtrim((string) config('billing.checkout.cancel_url'), '?&'),
                    appEnvironment: (string) app()->environment(),
                ),
            );
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->route('branch.edit')->withErrors(['billing' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['checkout_url' => $checkoutUrl]);
        }

        return redirect()->away($checkoutUrl);
    }

    /**
     * Redirect to Stripe Customer Portal (payments, invoices, subscription self-service hosted by Stripe).
     */
    public function portal(
        StartBranchBillingPortalRequest $request,
        CreateBillingPortalSession $portalSession,
        BillingProviderManager $billingProviders,
    ): RedirectResponse {
        abort_unless($request->user()?->hasRole(UserRole::Admin->value), 403);

        /** @phpstan-ignore-next-line */
        $branch = Branch::query()->findOrFail((int) $request->user()->branch_id);

        try {
            $gateway = $billingProviders->stripeGateway();
            if (! $gateway instanceof StripeSubscriptionGatewayContract) {
                return redirect()->route('branch.edit')->withErrors(['billing' => 'Stripe connector unavailable.']);
            }

            $returnUrl = route('branch.edit', [], true).'?tab=subscription';

            /** @phpstan-ignore-next-line */
            $portalUrl = $portalSession->handle($gateway, (string) $branch->provider_customer_id, $returnUrl);
        } catch (Throwable $e) {
            return redirect()->route('branch.edit')->withErrors(['billing' => $e->getMessage()]);
        }

        /** @phpstan-ignore-next-line */
        return redirect()->away($portalUrl);
    }

    protected function billingFailureResponse(
        Request $request,
        string $message,
        string $errorKey = 'billing',
    ): RedirectResponse|\Illuminate\Http\JsonResponse {
        if ($request->expectsJson()) {
            /** @phpstan-ignore-next-line */
            return response()->json(['errors' => [$errorKey => [$message]], 'message' => $message], 422);
        }

        /** @phpstan-ignore-next-line */
        return redirect()->route('branch.edit')->withErrors([$errorKey => $message]);
    }

    public function cancel(
        Request $request,
        BillingProviderManager $billingProviders,
    ): RedirectResponse {
        abort_unless($request->user()?->hasRole(UserRole::Admin->value), 403);

        /** @phpstan-ignore-next-line */
        if (! BillingConfiguration::stripeSecretConfigured()) {
            return redirect()->route('branch.edit')->withErrors(['billing' => 'Stripe is not configured.']);
        }

        /** @phpstan-ignore-next-line */
        $branch = Branch::query()->findOrFail((int) $request->user()->branch_id);

        /** @phpstan-ignore-next-line */
        if (($branch->provider_subscription_id ?? '') === '') {
            return redirect()->route('branch.edit')->withErrors(['billing' => 'There is no provider subscription attached to cancel.']);
        }

        $gateway = $billingProviders->stripeGateway();
        if (! $gateway instanceof StripeSubscriptionGatewayContract) {
            return redirect()->route('branch.edit')->withErrors(['billing' => 'Stripe connector unavailable.']);
        }

        try {
            $gateway->cancelSubscriptionAtPeriodEnd((string) $branch->provider_subscription_id);
        } catch (Throwable $e) {
            return redirect()->route('branch.edit')->withErrors(['billing' => 'Unable to contact Stripe right now ('.$e->getMessage().').']);
        }

        return redirect()->route('branch.edit')->with('success', 'Cancellation has been scheduled with Stripe. Confirm status after webhooks reconcile.');
    }
}
