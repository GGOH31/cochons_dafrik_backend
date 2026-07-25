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
            'phone' => ['required', 'string', 'max:10', 'unique:users,phone'],
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'fcm_token' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(AccountStatus::class)],
            
            // Nested shop validation for vendors
            'restaurant' => ['required_if:role,' . UserRole::VENDEUR->value, 'array'],
            'restaurant.name' => ['required_if:role,' . UserRole::VENDEUR->value, 'string', 'max:140'],
            'restaurant.description' => ['nullable', 'string'],
            'restaurant.commune' => ['required_if:role,' . UserRole::VENDEUR->value, 'string', 'max:80'],
            'restaurant.address' => ['nullable', 'string'],
            'restaurant.logo_file' => ['nullable', 'file', 'max:10240'],
            'restaurant.supporting_docs_file' => ['required_if:role,' . UserRole::VENDEUR->value, 'file', 'max:10240'],
        ];
    }
}
