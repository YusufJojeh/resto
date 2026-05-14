<?php

declare(strict_types=1);

namespace App\Modules\Branches\Requests;

use App\Enums\UserRole;
use App\Modules\Branches\Models\Branch;
use App\Support\Billing\BillingConfiguration;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StartBranchBillingPortalRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @phpstan-ignore-next-line */
        return $this->user()?->hasRole(UserRole::Admin->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! BillingConfiguration::billingPortalAvailable()) {
                $validator->errors()->add('billing', 'Stripe Customer Portal is not available in this environment.');

                return;
            }

            /** @phpstan-ignore-next-line */
            $branch = Branch::query()->find((int) $this->user()->branch_id);
            /** @phpstan-ignore-next-line */
            if (($branch?->provider_customer_id ?? '') === '') {
                $validator->errors()->add(
                    'billing',
                    'This workspace has no Stripe customer id yet. Complete checkout once or attach a customer manually before opening the portal.',
                );
            }
        });
    }
}
