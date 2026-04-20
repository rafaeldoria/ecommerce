<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => $this->filled('username') ? trim((string) $this->input('username')) : null,
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            'device_name' => trim((string) $this->input('device_name', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'username' => ['nullable', 'string', 'required_without:email'],
            'email' => ['nullable', 'email', 'required_without:username'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }
}
