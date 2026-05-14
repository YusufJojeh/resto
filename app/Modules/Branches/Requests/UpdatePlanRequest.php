<?php

namespace App\Modules\Branches\Requests;

use App\Enums\UserRole;
use App\Modules\Branches\Models\Plan;
use App\Modules\Branches\Requests\Concerns\NormalizesPlanPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    use NormalizesPlanPayload;

    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Admin->value) ?? false;
    }

    public function rules(): array
    {
        /** @var Plan $plan */
        $plan = $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('plans', 'slug')->ignore($plan->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_amount' => ['nullable', 'numeric', 'min:0'],
            'billing_interval' => ['nullable', 'string', Rule::in(['month', 'year'])],
            'provider_price_id' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'features' => ['required', 'array'],
            'limits' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);

        if (! $this->has('sort_order')) {
            $this->merge(['sort_order' => 0]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toPlanAttributes(): array
    {
        return [
            'name' => $this->validated('name'),
            'slug' => $this->validated('slug'),
            'description' => $this->validated('description'),
            'price_amount' => $this->validated('price_amount'),
            'billing_interval' => $this->validated('billing_interval'),
            'provider_price_id' => $this->input('provider_price_id') ?: null,
            'is_active' => $this->boolean('is_active'),
            'sort_order' => (int) ($this->validated('sort_order') ?? 0),
            'features' => $this->coerceFeatures($this->validated('features')),
            'limits' => $this->coerceLimits($this->validated('limits')),
        ];
    }
}
