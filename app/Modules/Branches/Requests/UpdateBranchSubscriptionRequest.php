<?php

namespace App\Modules\Branches\Requests;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Admin->value) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['plan_id', 'trial_ends_at', 'subscription_ends_at', 'current_period_ends_at'] as $nullableKey) {
            if ($nullableKey === 'plan_id') {
                if (
                    $this->input($nullableKey) === ''
                    || $this->input($nullableKey) === 'none'
                    || $this->input($nullableKey) === '__none__'
                ) {
                    $this->merge([$nullableKey => null]);
                }

                continue;
            }

            if ($this->input($nullableKey) === '') {
                $this->merge([$nullableKey => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'subscription_status' => ['required', 'string', 'in:'.implode(',', SubscriptionStatus::values())],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'current_period_ends_at' => ['nullable', 'date'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_status.in' => 'The selected subscription status is invalid.',
            'subscription_ends_at.after_or_equal' => 'Subscription end date must be after or equal to trial end date.',
        ];
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Contracts\Validation\Validator $validator): void {
            $this->validateTrialVsSubscriptionEnds($validator);
            $this->validateAssignablePlan($validator);
        });
    }

    protected function validateTrialVsSubscriptionEnds(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $trialEnds = $this->input('trial_ends_at');
        $subscriptionEnds = $this->input('subscription_ends_at');

        if ($trialEnds !== null && $subscriptionEnds !== null) {
            if (\Carbon\Carbon::parse($subscriptionEnds)->lessThan(\Carbon\Carbon::parse($trialEnds))) {
                $validator->errors()->add(
                    'subscription_ends_at',
                    'Commercial end date must be on or after trial end date when both are provided.'
                );
            }
        }
    }

    protected function validateAssignablePlan(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $planId = $this->input('plan_id');
        if ($planId === null) {
            return;
        }

        $branch = Branch::query()->findOrFail((int) $this->user()->branch_id);
        $plan = Plan::query()->find((int) $planId);

        if ($plan === null) {
            return;
        }

        if ($plan->allowsManualAssignment($branch->plan_id !== null ? (int) $branch->plan_id : null)) {
            return;
        }

        $validator->errors()->add('plan_id', 'Inactive plans cannot be assigned unless this branch already uses this plan.');
    }
}
