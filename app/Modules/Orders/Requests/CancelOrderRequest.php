<?php

namespace App\Modules\Orders\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([
            UserRole::Admin->value,
            UserRole::Manager->value,
            UserRole::Waiter->value,
        ]) ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
