<?php

declare(strict_types=1);

namespace App\Support\Subscription;

/**
 * Machine-stable subscription lifecycle / access outcome codes emitted by SubscriptionAccessEvaluator.
 */
final class SubscriptionAccessReason
{
    public const NO_BRANCH = 'no_branch';

    public const BRANCH_INACTIVE = 'inactive_branch';

    public const ACTIVE = 'active';

    public const TRIAL_ACTIVE = 'trial_active';

    public const TRIAL_EXPIRED = 'trial_expired';

    public const PAST_DUE_GRACE = 'past_due_grace';

    public const PAST_DUE_EXPIRED = 'past_due_expired';

    public const CANCELED_UNTIL_PERIOD_END = 'canceled_until_period_end';

    public const CANCELED_EXPIRED = 'canceled_expired';

    public const EXPIRED = 'expired';

    public const SUSPENDED = 'suspended';

    public const UNKNOWN = 'unknown';
}
