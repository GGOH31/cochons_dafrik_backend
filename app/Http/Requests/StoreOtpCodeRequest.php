<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOtpCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'code_hash' => ['required', 'string'],
            'expires_at' => ['required', 'date'],
            'used_at' => ['nullable', 'date'],
        ];
    }
}
