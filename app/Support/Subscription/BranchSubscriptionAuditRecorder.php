<?php

declare(strict_types=1);

namespace App\Support\Subscription;

use App\Modules\Branches\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class BranchSubscriptionAuditRecorder
{
    /**
     * @param  array{source:string, provider:?string, provider_event_id:?string}  $meta
     */
    public static function record(
        Branch $beforeBranch,
        Branch $afterBranch,
        SubscriptionAccessResult $beforeAccess,
        SubscriptionAccessResult $afterAccess,
        ?int $actorId,
        array $meta,
    ): void {
        if (! Schema::hasTable('branch_subscription_changes')) {
            return;
        }

        $oldStatus = $beforeBranch->subscription_status;
        $oldStatusVal = $oldStatus instanceof \BackedEnum ? $oldStatus->value : ($oldStatus ? (string) $oldStatus : null);

        $newStatus = $afterBranch->subscription_status;
        $newStatusVal = $newStatus instanceof \BackedEnum ? $newStatus->value : ($newStatus ? (string) $newStatus : null);

        DB::table('branch_subscription_changes')->insert([
            'branch_id' => $afterBranch->id,
            'actor_id' => $actorId,
            'source' => $meta['source'] ?? 'manual',
            'provider' => $meta['provider'] ?? null,
            'provider_event_id' => $meta['provider_event_id'] ?? null,
            'old_plan_id' => $beforeBranch->plan_id,
            'new_plan_id' => $afterBranch->plan_id,
            'old_status' => $oldStatusVal,
            'new_status' => $newStatusVal,
            'old_trial_ends_at' => $beforeBranch->trial_ends_at?->toIso8601String(),
            'new_trial_ends_at' => $afterBranch->trial_ends_at?->toIso8601String(),
            'old_subscription_ends_at' => $beforeBranch->subscription_ends_at?->toIso8601String(),
            'new_subscription_ends_at' => $afterBranch->subscription_ends_at?->toIso8601String(),
            'old_current_period_ends_at' => $beforeBranch->current_period_ends_at?->toIso8601String(),
            'new_current_period_ends_at' => $afterBranch->current_period_ends_at?->toIso8601String(),
            'old_access_allowed' => $beforeAccess->allowed,
            'new_access_allowed' => $afterAccess->allowed,
            'old_access_reason' => $beforeAccess->reasonCode,
            'new_access_reason' => $afterAccess->reasonCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
