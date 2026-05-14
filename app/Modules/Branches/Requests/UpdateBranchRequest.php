<?php

namespace App\Modules\Branches\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]) ?? false;
    }

    public function rules(): array
    {
        $branchId = $this->user()?->branch_id;

        return [
            'name'           => ['required', 'string', 'max:100'],
            'address'        => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'tax_rate'       => ['required', 'numeric', 'min:0', 'max:100'],
            'currency_code'  => ['required', 'string', 'max:5'],
            // branding
            'public_slug'    => ['nullable', 'string', 'max:80', 'alpha_dash', "unique:branches,public_slug,{$branchId}"],
            'is_public'      => ['boolean'],
            'business_name'  => ['nullable', 'string', 'max:150'],
            'tagline'        => ['nullable', 'string', 'max:200'],
            'story'          => ['nullable', 'string', 'max:5000'],
            'primary_color'  => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color'=> ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color'   => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'whatsapp'       => ['nullable', 'string', 'max:30'],
            'instagram_url'  => ['nullable', 'url', 'max:255'],
            'facebook_url'   => ['nullable', 'url', 'max:255'],
            'tiktok_url'     => ['nullable', 'url', 'max:255'],
            'google_maps_url'=> ['nullable', 'url', 'max:255'],
            'opening_hours'  => ['nullable', 'array'],
            'logo'           => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'cover'          => ['nullable', 'image', 'max:4096', 'mimes:jpg,jpeg,png,webp'],
        ];
    }
}
