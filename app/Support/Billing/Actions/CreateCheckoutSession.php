<?php

declare(strict_types=1);

namespace App\Support\Billing\Actions;

use App\Support\Billing\BillingConfiguration;
use App\Support\Billing\Contracts\BillingProviderContract;
use App\Support\Billing\Data\CreateSubscriptionCheckoutInput;
use InvalidArgumentException;

final class CreateCheckoutSession
{
    public function handle(BillingProviderContract $billing, CreateSubscriptionCheckoutInput $input): string
    {
        if (! BillingConfiguration::checkoutAvailable()) {
            throw new InvalidArgumentException('Billing checkout is not configured for this application.');
        }

        return $billing->createSubscriptionCheckout($input);
    }
}
