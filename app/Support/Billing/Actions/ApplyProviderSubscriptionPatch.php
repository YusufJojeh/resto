<?php

declare(strict_types=1);

namespace App\Support\Billing\Actions;

use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;
use App\Support\Billing\Data\ProviderSubscriptionPatch;
use App\Support\Subscription\BranchSubscriptionAuditRecorder;
use App\Support\Subscription\SubscriptionAccessEvaluator;
use Illuminate\Support\Facades\DB;

final class ApplyProviderSubscriptionPatch
{
    public function execute(ProviderSubscriptionPatch $patch): ?Branch
    {
        return DB::transaction(function () use ($patch): ?Branch {
            $branchModel = $this->resolveBranch($patch);

            if (! $branchModel instanceof Branch) {
                return null;
            }

            /** @phpstan-ignore-next-line */
            $fresh = $branchModel->fresh(['plan']);
            if ($fresh === null) {
                return null;
            }

            $beforeSnapshotBranch = Branch::withoutEvents(static fn (): ?Branch => Branch::query()->find($fresh->id));
            if (! $beforeSnapshotBranch instanceof Branch) {
                /** @phpstan-ignore-next-line */
                $beforeSnapshotBranch = $fresh;
            }

            /** @phpstan-ignore-next-line */
            if ($beforeSnapshotBranch instanceof Branch && $fresh->relationLoaded('plan')) {
                $beforeSnapshotBranch->setRelation('plan', $fresh->plan);
            }

            $beforeAccess = SubscriptionAccessEvaluator::evaluate($fresh);

            $updates = [];

            if ($patch->subscriptionStatus !== null) {
                $updates['subscription_status'] = $patch->subscriptionStatus;
            }

            if ($patch->planId !== null) {
                $updates['plan_id'] = $patch->planId;
                $slug = Plan::query()->whereKey($patch->planId)->value('slug');
                if (is_string($slug) && $slug !== '') {
                    $updates['subscription_plan'] = $slug;
                }
            }

            if ($patch->providerName !== null && $patch->providerName !== '') {
                $updates['provider_name'] = $patch->providerName;
            }

            if ($patch->providerCustomerId !== null && $patch->providerCustomerId !== '') {
                $updates['provider_customer_id'] = $patch->providerCustomerId;
            }

            if ($patch->providerSubscriptionId !== null && $patch->providerSubscriptionId !== '') {
                $updates['provider_subscription_id'] = $patch->providerSubscriptionId;
            }

            if ($patch->clearTrialEnds) {
                $updates['trial_ends_at'] = null;
            } elseif ($patch->trialEndsAt !== null) {
                /** @phpstan-ignore-next-line */
                $updates['trial_ends_at'] = $patch->trialEndsAt;
            }

            if ($patch->currentPeriodEndsAt !== null) {
                /** @phpstan-ignore-next-line */
                $updates['current_period_ends_at'] = $patch->currentPeriodEndsAt;
            }

            if ($patch->subscriptionEndsAt !== null) {
                /** @phpstan-ignore-next-line */
                $updates['subscription_ends_at'] = $patch->subscriptionEndsAt;
            }

            /** @phpstan-ignore-next-line */
            if ($updates === []) {
                return null;
            }

            $fresh->forceFill($updates)->saveQuietly();

            /** @phpstan-ignore-next-line */
            $after = $fresh->fresh(['plan']);

            /** @phpstan-ignore-next-line */
            if ($after === null) {
                return null;
            }

            $afterAccess = SubscriptionAccessEvaluator::evaluate($after);

            BranchSubscriptionAuditRecorder::record(
                $beforeSnapshotBranch,
                $after,
                $beforeAccess,
                $afterAccess,
                actorId: null,
                meta: [
                    'source' => 'stripe',
                    'provider' => $patch->provider,
                    'provider_event_id' => $patch->providerEventId,
                ],
            );

            return $after;
        });
    }

    /**
     * Authoritative lookups first (subscription id → single customer uniqueness), fallback to checkout hints only when trusted.
     */
    private function resolveBranch(ProviderSubscriptionPatch $patch): ?Branch
    {
        /** @phpstan-ignore-next-line */
        if (($patch->providerSubscriptionId ?? '') !== '') {
            $bySub = Branch::query()->firstWhere(
                /** @phpstan-ignore-next-line */
                'provider_subscription_id',
                $patch->providerSubscriptionId,
            );

            /** @phpstan-ignore-next-line */
            if ($bySub instanceof Branch) {
                return $bySub;
            }
        }

        if (($patch->providerCustomerId ?? '') !== '') {
            $matches = Branch::query()
                /** @phpstan-ignore-next-line */
                ->where('provider_customer_id', $patch->providerCustomerId)
                ->get();

            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        if ($patch->trustedBranchResolutionFromHintOnly && ($patch->resolveBranchIdHint ?? null) !== null) {
            return Branch::query()->find((int) $patch->resolveBranchIdHint);
        }

        /** @phpstan-ignore-next-line */
        return null;
    }
}
