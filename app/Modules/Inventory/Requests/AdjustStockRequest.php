<?php

namespace App\Modules\Inventory\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]) ?? false;
    }

    public function rules(): array
    {
        return [
            'adjustment' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
