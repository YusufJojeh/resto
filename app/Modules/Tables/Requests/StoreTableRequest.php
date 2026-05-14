<?php

namespace App\Modules\Tables\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]) ?? false;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1', Rule::unique('restaurant_tables', 'number')->where('branch_id', $this->user()->branch_id)],
            'name' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
