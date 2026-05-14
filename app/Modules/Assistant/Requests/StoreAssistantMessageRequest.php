<?php

namespace App\Modules\Assistant\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssistantMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:' . (int) config('assistant.max_prompt_length', 5000)],
            'current_path' => ['nullable', 'string', 'max:255'],
            'client_message_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
