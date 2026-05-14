<?php

declare(strict_types=1);

namespace App\Support\Billing\Contracts;

/**
 * Narrow gateway for Stripe subscription lifecycle side-effects outside checkout.
 */
interface StripeSubscriptionGatewayContract
{
    public function cancelSubscriptionAtPeriodEnd(string $stripeSubscriptionId): void;

    /**
     * Hydrate subscription arrays for webhook bodies that only embed ids.
     *
     * @return array<string, mixed>
     */
    public function retrieveSubscription(string $stripeSubscriptionId): array;

    /**
     * Creates a Stripe Customer Portal session; returns hosted URL.
     */
    public function createBillingPortalSession(string $stripeCustomerId, string $returnUrl): string;
}
