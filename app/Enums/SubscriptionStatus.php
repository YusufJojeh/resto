<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Trialing = 'trialing';
    /** Awaiting renewal payment; operational access follows grace rules in SubscriptionAccessEvaluator. */
    case PastDue = 'past_due';
    /** Cancel-at-period-end: access continues until `current_period_ends_at` or `subscription_ends_at`. */
    case Canceled = 'canceled';
    case Expired = 'expired';
    case Suspended = 'suspended';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }
}