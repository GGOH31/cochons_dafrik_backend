<?php

namespace App\Http\Requests;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'role' => ['sometimes', new Enum(UserRole::class)],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone,' . $userId],
            'full_name' => ['sometimes', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:6'],
            'fcm_token' => ['nullable', 'string'],
            'status' => ['sometimes', new Enum(AccountStatus::class)],
        ];
    }
}
