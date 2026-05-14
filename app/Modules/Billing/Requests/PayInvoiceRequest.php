<?php

namespace App\Modules\Billing\Requests;

use App\Enums\InvoicePaymentMethod;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value, UserRole::Cashier->value]) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in([
                InvoicePaymentMethod::Cash->value,
                InvoicePaymentMethod::Card->value,
            ])],
        ];
    }
}
