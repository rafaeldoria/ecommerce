<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminMfaChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'challenge_id' => trim((string) $this->input('challenge_id', '')),
            'code' => $this->filled('code') ? trim((string) $this->input('code')) : null,
            'recovery_code' => $this->filled('recovery_code') ? trim((string) $this->input('recovery_code')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'challenge_id' => ['required', 'string', 'uuid'],
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string', 'required_without:code'],
        ];
    }
}
