<?php

namespace Tests\Unit\Models;

use App\Enums\SubscriptionStatus;
use App\Modules\Branches\Models\Branch;
use App\Support\Subscription\SubscriptionAccessReason;
use Tests\TestCase;

class BranchSubscriptionTest extends TestCase
{
    public function test_active_branch_has_subscription_access(): void
    {
        $branch = new Branch([
            'is_active' => true,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $this->assertTrue($branch->hasSubscriptionAccess());
        $this->assertSame(SubscriptionAccessReason::ACTIVE, $branch->subscriptionAccessReason());
    }

    public function test_trialing_branch_has_access_until_trial_expires(): void
    {
        $branch = new Branch([
            'is_active' => true,
            'subscription_status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->assertTrue($branch->hasSubscriptionAccess());
        $this->assertSame(SubscriptionAccessReason::TRIAL_ACTIVE, $branch->subscriptionAccessReason());
    }

    public function test_trialing_branch_loses_access_after_trial_expires(): void
    {
        $branch = new Branch([
            'is_active' => true,
            'subscription_status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->subMinute(),
        ]);

        $this->assertFalse($branch->hasSubscriptionAccess());
        $this->assertSame(SubscriptionAccessReason::TRIAL_EXPIRED, $branch->subscriptionAccessReason());
    }

    public function test_null_subscription_status_denies_access(): void
    {
        $branch = new Branch([
            'is_active' => true,
            'subscription_status' => null,
        ]);

        $this->assertFalse($branch->hasSubscriptionAccess());
        $this->assertSame(SubscriptionAccessReason::UNKNOWN, $branch->subscriptionAccessReason());
    }

    public function test_expired_suspended_branch_statuses(): void
    {
        $expired = new Branch([
            'is_active' => true,
            'subscription_status' => SubscriptionStatus::Expired,
        ]);

        $suspended = new Branch([
            'is_active' => true,
            'subscription_status' => SubscriptionStatus::Suspended,
        ]);

        $inactive = new Branch([
            'is_active' => false,
            'subscription_status' => SubscriptionStatus::Active,
        ]);

        $this->assertFalse($expired->hasSubscriptionAccess());
        $this->assertSame(SubscriptionAccessReason::EXPIRED, $expired->subscriptionAccessReason());

        $this->assertFalse($suspended->hasSubscriptionAccess());
        $this->assertSame(SubscriptionAccessReason::SUSPENDED, $suspended->subscriptionAccessReason());

        $this->assertFalse($inactive->hasSubscriptionAccess());
        $this->assertSame(SubscriptionAccessReason::BRANCH_INACTIVE, $inactive->subscriptionAccessReason());
    }
}
