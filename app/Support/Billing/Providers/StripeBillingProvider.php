<?php

declare(strict_types=1);

namespace App\Support\Billing\Providers;

use App\Support\Billing\Contracts\BillingProviderContract;
use App\Support\Billing\Contracts\StripeSubscriptionGatewayContract;
use App\Support\Billing\Data\CreateSubscriptionCheckoutInput;
use Stripe\Exception\ExceptionInterface as StripeException;
use Stripe\StripeClient;

final class StripeBillingProvider implements BillingProviderContract, StripeSubscriptionGatewayContract
{
    public function __construct(
        private readonly ?string $secretKey,
        private readonly ?string $apiVersion,
    ) {}

    public function connectorId(): string
    {
        return 'stripe';
    }

    public function client(): StripeClient
    {
        if (! filled($this->secretKey)) {
            throw new \LogicException('Stripe secret key missing.');
        }

        return new StripeClient([
            'api_key' => $this->secretKey,
            'stripe_version' => $this->apiVersion,
        ]);
    }

    /**
     * @throws StripeException
     */
    public function createSubscriptionCheckout(CreateSubscriptionCheckoutInput $input): string
    {
        $meta = [
            'branch_id' => (string) $input->branchId,
            'plan_id' => (string) $input->planId,
            'user_id' => (string) $input->userId,
            'app_environment' => $input->appEnvironment,
        ];

        $session = $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [
                ['price' => $input->stripePriceId, 'quantity' => 1],
            ],
            'automatic_tax' => ['enabled' => false],
            'success_url' => $input->successUrl,
            'cancel_url' => $input->cancelUrl,
            'client_reference_id' => 'branch:'.$input->branchId,
            'metadata' => $meta,
            'subscription_data' => [
                'metadata' => $meta,
            ],
        ]);

        if ($session->url === null || $session->url === '') {
            throw new \RuntimeException('Stripe checkout URL missing.');
        }

        /** @phpstan-ignore-next-line Stripe SDK looseness */
        return (string) $session->url;
    }

    public function cancelSubscriptionAtPeriodEnd(string $stripeSubscriptionId): void
    {
        $this->client()->subscriptions->update($stripeSubscriptionId, ['cancel_at_period_end' => true]);
    }

    public function retrieveSubscription(string $stripeSubscriptionId): array
    {
        $sub = $this->client()->subscriptions->retrieve($stripeSubscriptionId, [
            'expand' => ['items.data.price'],
        ]);

        return $sub instanceof \Stripe\Subscription ? $sub->toArray() : (array) $sub;
    }

    /**
     * @throws StripeException
     */
    public function createBillingPortalSession(string $stripeCustomerId, string $returnUrl): string
    {
        $session = $this->client()->billingPortal->sessions->create([
            'customer' => $stripeCustomerId,
            'return_url' => $returnUrl,
        ]);

        if ($session->url === null || $session->url === '') {
            throw new \RuntimeException('Stripe billing portal URL missing.');
        }

        /** @phpstan-ignore-next-line Stripe SDK looseness */
        return (string) $session->url;
    }
}
