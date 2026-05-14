<?php

declare(strict_types=1);

namespace App\Support\Billing\Data;

use App\Enums\SubscriptionStatus;
use Carbon\CarbonImmutable;

final readonly class ProviderSubscriptionPatch
{
    public function __construct(
        public string $provider,
        public string $providerEventId,
        public string $eventType,
        public ?SubscriptionStatus $subscriptionStatus = null,
        public ?int $planId = null,
        public ?CarbonImmutable $trialEndsAt = null,
        public ?CarbonImmutable $currentPeriodEndsAt = null,
        public ?CarbonImmutable $subscriptionEndsAt = null,
        public ?string $providerName = null,
        public ?string $providerCustomerId = null,
        public ?string $providerSubscriptionId = null,
        public ?int $resolveBranchIdHint = null,
        public bool $trustedBranchResolutionFromHintOnly = false,
        public bool $clearTrialEnds = false,
    ) {}
}
