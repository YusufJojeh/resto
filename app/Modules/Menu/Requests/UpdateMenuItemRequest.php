<?php

namespace App\Modules\Menu\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]) ?? false;
    }

    public function rules(): array
    {
        $branchId = $this->user()?->branch_id;

        return [
            'category_id' => ['required', Rule::exists('menu_categories', 'id')->where('branch_id', $branchId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
