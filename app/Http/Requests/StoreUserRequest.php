<?php

namespace App\Http\Requests;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', new Enum(UserRole::class)],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'fcm_token' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(AccountStatus::class)],
        ];
    }
}
