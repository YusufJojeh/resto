<?php

declare(strict_types=1);

namespace App\Support\Billing\Actions;

use App\Support\Billing\BillingConfiguration;
use App\Support\Billing\Contracts\StripeSubscriptionGatewayContract;
use InvalidArgumentException;

final class CreateBillingPortalSession
{
    public function handle(
        StripeSubscriptionGatewayContract $gateway,
        string $stripeCustomerId,
        string $returnUrl,
    ): string {
        if (! BillingConfiguration::billingPortalAvailable()) {
            throw new InvalidArgumentException('Stripe billing portal is not configured for this application.');
        }

        return $gateway->createBillingPortalSession($stripeCustomerId, $returnUrl);
    }
}
