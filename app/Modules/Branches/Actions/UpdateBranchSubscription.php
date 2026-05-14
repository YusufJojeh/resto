<?php

declare(strict_types=1);

namespace App\Modules\Branches\Actions;

use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;
use App\Notifications\OperationalNotification;
use App\Support\Notifications\BranchRoleNotifier;
use App\Support\Subscription\BranchSubscriptionAuditRecorder;
use App\Support\Subscription\SubscriptionAccessEvaluator;
use Illuminate\Support\Facades\DB;

class UpdateBranchSubscription
{
    /**
     * Update branch subscription fields with audit logging.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Branch $branch, array $data, int $actorId): Branch
    {
        $fresh = $branch->fresh(['plan']);
        if ($fresh === null) {
            /** @phpstan-ignore-next-line */
            return $branch;
        }

        $beforeAssessment = SubscriptionAccessEvaluator::evaluate($fresh);

        /** @phpstan-ignore-next-line */
        $auditBefore = Branch::withoutEvents(static fn (): ?Branch => Branch::query()->find($fresh->id));
        if (! $auditBefore instanceof Branch) {
            $auditBefore = $fresh;
        }
        if ($fresh->relationLoaded('plan')) {
            /** @phpstan-ignore-next-line */
            $auditBefore->setRelation('plan', $fresh->plan);
        }

        return DB::transaction(function () use ($fresh, $data, $actorId, $beforeAssessment, $auditBefore): Branch {
            $incoming = $data['subscription_status'];

            /** @phpstan-ignore-next-line */
            $subscriptionStatusEnum = $incoming instanceof \App\Enums\SubscriptionStatus
                ? $incoming
                /** @phpstan-ignore-next-line */
                : \App\Enums\SubscriptionStatus::from((string) $incoming);

            $updatePayload = [
                'subscription_status' => $subscriptionStatusEnum,
            ];

            if (array_key_exists('trial_ends_at', $data)) {
                /** @phpstan-ignore-next-line */
                $trialRaw = $data['trial_ends_at'];
                $updatePayload['trial_ends_at'] = ($trialRaw !== null && $trialRaw !== '')
                    /** @phpstan-ignore-next-line */
                    ? \Carbon\Carbon::parse($trialRaw)
                    : null;
            }

            if (array_key_exists('subscription_ends_at', $data)) {
                /** @phpstan-ignore-next-line */
                $subRaw = $data['subscription_ends_at'];
                $updatePayload['subscription_ends_at'] = ($subRaw !== null && $subRaw !== '')
                    /** @phpstan-ignore-next-line */
                    ? \Carbon\Carbon::parse($subRaw)
                    : null;
            }

            if (array_key_exists('current_period_ends_at', $data)) {
                /** @phpstan-ignore-next-line */
                $periodRaw = $data['current_period_ends_at'];
                $updatePayload['current_period_ends_at'] = $periodRaw !== null && $periodRaw !== ''
                    /** @phpstan-ignore-next-line */
                    ? \Carbon\Carbon::parse($periodRaw)
                    : null;
            }

            if (array_key_exists('plan_id', $data)) {
                /** @phpstan-ignore-next-line */
                $planIdVal = $data['plan_id'];
                /** @phpstan-ignore-next-line */
                $updatePayload['plan_id'] = $planIdVal;
                $slugFromPlan = $planIdVal ? Plan::query()->whereKey($planIdVal)->value('slug') : null;
                if (is_string($slugFromPlan) && $slugFromPlan !== '') {
                    $updatePayload['subscription_plan'] = $slugFromPlan;
                }
            }

            $fresh->forceFill($updatePayload)->saveQuietly();

            /** @phpstan-ignore-next-line */
            $after = $fresh->fresh(['plan']);
            $afterAssessment = SubscriptionAccessEvaluator::evaluate($after ?? $fresh);

            BranchSubscriptionAuditRecorder::record(
                $auditBefore,
                /** @phpstan-ignore-next-line */
                $after ?? $fresh,
                $beforeAssessment,
                $afterAssessment,
                actorId: $actorId,
                meta: [
                    'source' => 'manual',
                    'provider' => null,
                    'provider_event_id' => null,
                ],
            );

            app(BranchRoleNotifier::class)->notifyByRoles(
                (int) $fresh->branch_id,
                ['admin', 'manager'],
                new OperationalNotification(
                    'subscription_event',
                    'Subscription updated',
                    'Branch subscription/access metadata was updated.',
                    (int) $fresh->branch_id,
                ),
            );

            /** @phpstan-ignore-next-line */
            return $after ?? $fresh;
        });
    }
}
