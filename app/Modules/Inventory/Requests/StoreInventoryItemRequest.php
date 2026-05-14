<?php

namespace App\Modules\Inventory\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]) ?? false;
    }

    public function rules(): array
    {
        return [
            'menu_item_id' => ['nullable', 'exists:menu_items,id'],
            'name' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:20'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'low_threshold' => ['required', 'numeric', 'min:0'],
        ];
    }
}
