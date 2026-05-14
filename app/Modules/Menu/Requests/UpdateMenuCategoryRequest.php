<?php

namespace App\Modules\Menu\Requests;

use App\Enums\UserRole;
use App\Modules\Menu\Models\MenuCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]) ?? false;
    }

    public function rules(): array
    {
        /** @var MenuCategory $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('menu_categories', 'name')->where('branch_id', $this->user()->branch_id)->ignore($category)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
