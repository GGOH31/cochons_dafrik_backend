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
            'shop' => ['required_if:role,' . UserRole::VENDEUR->value, 'array'],
            'shop.name' => ['required_if:role,' . UserRole::VENDEUR->value, 'string', 'max:140'],
            'shop.description' => ['nullable', 'string'],
            'shop.commune' => ['required_if:role,' . UserRole::VENDEUR->value, 'string', 'max:80'],
            'shop.address' => ['nullable', 'string'],
            'shop.logo_file' => ['nullable', 'file', 'image', 'max:10240'],
            'shop.supporting_docs_file' => ['required_if:role,' . UserRole::VENDEUR->value, 'file', 'max:10240'],
        ];
    }
}
