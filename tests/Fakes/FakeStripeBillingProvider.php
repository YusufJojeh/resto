<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Support\Billing\Contracts\BillingProviderContract;
use App\Support\Billing\Contracts\StripeSubscriptionGatewayContract;
use App\Support\Billing\Data\CreateSubscriptionCheckoutInput;

final class FakeStripeBillingProvider implements BillingProviderContract, StripeSubscriptionGatewayContract
{
    /** @var list<string> */
    public array $cancelCalls = [];

    /** @var array<string, mixed> keyed by Stripe subscription ids */
    public array $subscriptions = [];

    /** @phpstan-ignore-next-line */
    public bool $checkoutShouldThrow = false;

    /** @phpstan-ignore-next-line */
    public bool $retrieveShouldThrow = false;

    public function connectorId(): string
    {
        return 'stripe';
    }

    public function createSubscriptionCheckout(CreateSubscriptionCheckoutInput $input): string
    {
        /** @phpstan-ignore-next-line */
        if ($this->checkoutShouldThrow) {
            throw new \RuntimeException('checkout aborted');
        }

        return 'https://checkout.restocafe.test/session';
    }

    public function cancelSubscriptionAtPeriodEnd(string $stripeSubscriptionId): void
    {
        $this->cancelCalls[] = $stripeSubscriptionId;
    }

    public function createBillingPortalSession(string $stripeCustomerId, string $returnUrl): string
    {
        return 'https://billing-portal.restocafe.test/session';
    }

    public function retrieveSubscription(string $stripeSubscriptionId): array
    {
        /** @phpstan-ignore-next-line */
        if ($this->retrieveShouldThrow) {
            throw new \RuntimeException('retrieve failed');
        }

        /** @phpstan-ignore-next-line */
        if (isset($this->subscriptions[$stripeSubscriptionId]) && is_array($this->subscriptions[$stripeSubscriptionId])) {
            /** @phpstan-ignore-next-line */
            /** @phpstan-return array<string,mixed>
             */
            return $this->subscriptions[$stripeSubscriptionId];
        }

        /** @phpstan-ignore-next-line */
        return [];
    }
}
