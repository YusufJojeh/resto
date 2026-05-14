<?php

declare(strict_types=1);

namespace App\Support\Billing\Stripe;

use App\Enums\SubscriptionStatus;
use App\Modules\Branches\Models\Plan;
use Carbon\CarbonImmutable;

final readonly class ResolvedStripeLifecycle
{
    public function __construct(
        public SubscriptionStatus $status,
    ) {}
}

final class StripePlanResolver
{
    public static function planIdFromStripePrice(?string $priceId): ?int
    {
        if ($priceId === null || $priceId === '') {
            return null;
        }

        return Plan::query()
            ->where('provider_price_id', '=', $priceId)
            ->orderByRaw('CASE WHEN is_active = 1 THEN 0 ELSE 1 END')
            ->value('id');
    }
}

final class SubscriptionStatusLookup
{
    /**
     * @param  array<string, mixed>  $subscription
     */
    public static function fromStripeSubscription(array $subscription): ResolvedStripeLifecycle
    {
        $raw = strtolower((string) ($subscription['status'] ?? ''));

        $cancelAtPeriodEnd = (bool) ($subscription['cancel_at_period_end'] ?? false);

        if ($cancelAtPeriodEnd && $raw === 'active') {
            return new ResolvedStripeLifecycle(SubscriptionStatus::Canceled);
        }

        if ($raw === 'canceled' || $raw === 'cancelled') {
            $until = self::currentPeriodEnd($subscription);
            $status = ($until instanceof CarbonImmutable && $until->isFuture())
                ? SubscriptionStatus::Canceled
                : SubscriptionStatus::Expired;

            return new ResolvedStripeLifecycle($status);
        }

        return new ResolvedStripeLifecycle(match ($raw) {
            'active' => SubscriptionStatus::Active,
            'paused' => SubscriptionStatus::Suspended,
            'trialing' => SubscriptionStatus::Trialing,
            'past_due' => SubscriptionStatus::PastDue,
            'unpaid' => SubscriptionStatus::PastDue,
            'incomplete' => SubscriptionStatus::Suspended,
            'incomplete_expired' => SubscriptionStatus::Expired,
            default => SubscriptionStatus::Suspended,
        });
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    public static function trialEnd(array $subscription): ?CarbonImmutable
    {
        $ts = $subscription['trial_end'] ?? null;
        if ($ts === null || $ts === '') {
            return null;
        }

        return CarbonImmutable::createFromTimestamp((int) $ts);
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    public static function currentPeriodEnd(array $subscription): ?CarbonImmutable
    {
        $ts = $subscription['current_period_end'] ?? null;
        if ($ts === null || $ts === '') {
            return null;
        }

        return CarbonImmutable::createFromTimestamp((int) $ts);
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    public static function computedSubscriptionEnd(array $subscription): ?CarbonImmutable
    {
        $cancelTs = $subscription['cancel_at'] ?? $subscription['canceled_at'] ?? null;
        if ($cancelTs !== null && $cancelTs !== '') {
            return CarbonImmutable::createFromTimestamp((int) $cancelTs);
        }

        return self::currentPeriodEnd($subscription);
    }

    /** @param  array<string, mixed>  $subscription */
    public static function firstPriceId(array $subscription): ?string
    {
        $items = $subscription['items']['data'] ?? [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $pid = $item['price']['id'] ?? null;
            if (is_string($pid) && $pid !== '') {
                return $pid;
            }
        }

        return null;
    }
}
