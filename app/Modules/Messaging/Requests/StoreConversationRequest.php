<?php

namespace App\Modules\Messaging\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['direct', 'group'])],
            'title' => ['nullable', 'string', 'max:150'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('type') !== 'direct') {
                return;
            }

            $ids = collect($this->input('participant_ids', []))
                ->map(fn ($id) => (int) $id)
                ->unique();

            if ($this->user() !== null) {
                $ids->push((int) $this->user()->id);
            }

            if ($ids->unique()->count() !== 2) {
                $validator->errors()->add('participant_ids', 'Direct conversations must have exactly two users.');
            }
        });
    }
}
