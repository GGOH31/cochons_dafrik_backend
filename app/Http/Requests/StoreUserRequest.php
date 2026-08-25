<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Public self-registration is client-only: vendeur accounts are created by an
     * admin (see AdminController::createRestaurant), never through this form.
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'in:client'],
            'phone' => ['required', 'string', 'max:10', 'unique:users,phone'],
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'fcm_token' => ['nullable', 'string'],
        ];
    }
}
