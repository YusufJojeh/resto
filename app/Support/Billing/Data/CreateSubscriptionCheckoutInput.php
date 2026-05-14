<?php

declare(strict_types=1);

namespace App\Support\Billing\Data;

final readonly class CreateSubscriptionCheckoutInput
{
    public function __construct(
        public int $branchId,
        public int $planId,
        public int $userId,
        public string $stripePriceId,
        public string $successUrl,
        public string $cancelUrl,
        public string $appEnvironment,
    ) {}
}
