<?php

declare(strict_types=1);

namespace App\Support\Subscription;

use App\Enums\SubscriptionStatus;
use App\Modules\Branches\Models\Branch;
use Carbon\CarbonInterface;

/**
 * Single source of truth for subscription lifecycle vs operational access for a branch snapshot.
 */
final class SubscriptionAccessEvaluator
{
    public static function graceDays(): int
    {
        $days = (int) config('subscriptions.grace_days', 3);

        return max(0, $days);
    }

    /**
     * When the authenticated user has no Branch row attachment.
     */
    public static function noBranchAttached(): SubscriptionAccessResult
    {
        return new SubscriptionAccessResult(
            false,
            SubscriptionAccessReason::NO_BRANCH,
            'No branch is assigned to this account.'
        );
    }

    /**
     * Full evaluation for any Branch model instance that exists in the DB.
     */
    public static function evaluate(Branch $branch): SubscriptionAccessResult
    {
        if (! $branch->is_active) {
            return new SubscriptionAccessResult(
                false,
                SubscriptionAccessReason::BRANCH_INACTIVE,
                'This branch is deactivated and cannot operate the workspace.'
            );
        }

        $status = self::normalizedStatus($branch);

        if ($status === null) {
            return self::deny(
                SubscriptionAccessReason::UNKNOWN,
                'No subscription status is configured for this branch.'
            );
        }

        return match ($status) {
            SubscriptionStatus::Active => self::active(),
            SubscriptionStatus::Trialing => self::trialing($branch),
            SubscriptionStatus::PastDue => self::pastDue($branch),
            SubscriptionStatus::Canceled => self::canceled($branch),
            SubscriptionStatus::Expired => self::deny(
                SubscriptionAccessReason::EXPIRED,
                'The subscription has expired.'
            ),
            SubscriptionStatus::Suspended => self::deny(
                SubscriptionAccessReason::SUSPENDED,
                'The subscription has been suspended.'
            ),
            default => self::deny(
                SubscriptionAccessReason::UNKNOWN,
                'Subscription status cannot be interpreted for access.'
            ),
        };
    }

    private static function active(): SubscriptionAccessResult
    {
        return new SubscriptionAccessResult(
            true,
            SubscriptionAccessReason::ACTIVE,
            'Subscription is active.'
        );
    }

    private static function trialing(Branch $branch): SubscriptionAccessResult
    {
        $trialEndsAt = $branch->trial_ends_at;

        if ($trialEndsAt === null || $trialEndsAt->isFuture()) {
            return new SubscriptionAccessResult(
                true,
                SubscriptionAccessReason::TRIAL_ACTIVE,
                'Trial access is active.'
            );
        }

        return new SubscriptionAccessResult(
            false,
            SubscriptionAccessReason::TRIAL_EXPIRED,
            'The trial period has ended.'
        );
    }

    /**
     * Past due: unpaid / failed renewal states. Operational access persists until:
     *   anchor subscription_ends_at (preferred) otherwise current_period_ends_at,
     *   extended by configurable grace_days (after the anchor instant).
     * If neither anchor exists, deny with a deterministic outcome (configuration required).
     */
    private static function pastDue(Branch $branch): SubscriptionAccessResult
    {
        $anchor = self::pastDueBillingAnchor($branch);

        if ($anchor === null) {
            return new SubscriptionAccessResult(
                false,
                SubscriptionAccessReason::PAST_DUE_EXPIRED,
                'Payment is overdue and no renewal anchor date was provided to compute access.'
            );
        }

        $graceEnd = $anchor->clone()->addDays(self::graceDays());

        if (now()->lessThanOrEqualTo($graceEnd)) {
            $suffix = self::graceDays() > 0
                ? sprintf('Grace continues until %s.', $graceEnd->toIso8601String())
                : 'Operational access applies until the configured anchor.';

            return new SubscriptionAccessResult(
                true,
                SubscriptionAccessReason::PAST_DUE_GRACE,
                'Payment is overdue; access is temporarily allowed within the grace rules. '.$suffix
            );
        }

        return new SubscriptionAccessResult(
            false,
            SubscriptionAccessReason::PAST_DUE_EXPIRED,
            'Payment grace has ended.'
        );
    }

    /**
     * Canceled subscriptions keep access until the current commercial period completes.
     * Prefer current_period_ends_at; fall back to subscription_ends_at.
     */
    private static function canceled(Branch $branch): SubscriptionAccessResult
    {
        $until = $branch->current_period_ends_at ?? $branch->subscription_ends_at;

        if ($until === null) {
            return new SubscriptionAccessResult(
                false,
                SubscriptionAccessReason::CANCELED_EXPIRED,
                'The subscription has been canceled and no service end date has been configured.'
            );
        }

        if (now()->lessThanOrEqualTo($until)) {
            return new SubscriptionAccessResult(
                true,
                SubscriptionAccessReason::CANCELED_UNTIL_PERIOD_END,
                sprintf('Cancellation is pending; operational access stays available until %s.', $until->toIso8601String())
            );
        }

        return new SubscriptionAccessResult(
            false,
            SubscriptionAccessReason::CANCELED_EXPIRED,
            'Canceled service availability has concluded.'
        );
    }

    private static function normalizedStatus(Branch $branch): ?SubscriptionStatus
    {
        $raw = $branch->subscription_status;
        if ($raw instanceof SubscriptionStatus) {
            return $raw;
        }

        if ($raw === null || $raw === '') {
            return null;
        }

        return SubscriptionStatus::tryFrom((string) $raw);
    }

    private static function deny(string $reasonCode, string $explanation): SubscriptionAccessResult
    {
        return new SubscriptionAccessResult(false, $reasonCode, $explanation);
    }

    /**
     * Past due anchors on the billed-through / renewal-boundary datetime.
     */
    private static function pastDueBillingAnchor(Branch $branch): ?CarbonInterface
    {
        return $branch->subscription_ends_at ?? $branch->current_period_ends_at;
    }
}
