<?php

declare(strict_types=1);

namespace App\Modules\Branches\Requests;

use App\Enums\UserRole;
use App\Modules\Branches\Models\Plan;
use App\Support\Billing\BillingConfiguration;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StartBranchBillingCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @phpstan-ignore-next-line */
        return $this->user()?->hasRole(UserRole::Admin->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! BillingConfiguration::checkoutAvailable()) {
                /** @phpstan-ignore-next-line */
                $validator->errors()->add('billing', 'Billing checkout is not configured. Enable billing and set Stripe keys plus checkout URLs.');

                return;
            }

            /** @phpstan-ignore-next-line */
            $plan = Plan::query()->find((int) $this->input('plan_id'));
            if ($plan === null) {
                return;
            }

            /** @phpstan-ignore-next-line */
            if ($plan->isPurchasableForStripeCheckout()) {
                return;
            }

            if (! $plan->is_active) {
                /** @phpstan-ignore-next-line */
                $validator->errors()->add('plan_id', 'Inactive plans cannot be purchased through Stripe checkout.');
            } else {
                /** @phpstan-ignore-next-line */
                $validator->errors()->add('plan_id', 'This plan does not have a Stripe price id configured.');
            }
        });
    }
}
