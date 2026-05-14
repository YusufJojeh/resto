<?php

namespace App\Modules\Tables\Requests;

use App\Enums\TableStatus;
use App\Enums\UserRole;
use App\Modules\Tables\Models\RestaurantTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]) ?? false;
    }

    public function rules(): array
    {
        /** @var RestaurantTable $table */
        $table = $this->route('table');

        return [
            'number' => ['required', 'integer', 'min:1', Rule::unique('restaurant_tables', 'number')->where('branch_id', $this->user()->branch_id)->ignore($table)],
            'name' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['sometimes', Rule::in([TableStatus::Available->value, TableStatus::Reserved->value])],
        ];
    }
}
