<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Support\Billing\Contracts\BillingProviderContract;
use App\Support\Billing\Contracts\StripeSubscriptionGatewayContract;
use Illuminate\Contracts\Container\Container;

final class BillingProviderManager
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function billingProvider(): BillingProviderContract
    {
        return $this->container->make(BillingProviderContract::class);
    }

    public function stripeGateway(): ?StripeSubscriptionGatewayContract
    {
        if (config('billing.provider') !== 'stripe') {
            return null;
        }

        return $this->container->make(StripeSubscriptionGatewayContract::class);
    }
}
