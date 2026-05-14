<?php

declare(strict_types=1);

namespace App\Support\Billing\Contracts;

use App\Support\Billing\Data\CreateSubscriptionCheckoutInput;

interface BillingProviderContract
{
    public function connectorId(): string;

    /**
     * @throws \Stripe\Exception\ExceptionInterface|\Throwable
     */
    public function createSubscriptionCheckout(CreateSubscriptionCheckoutInput $input): string;
}
