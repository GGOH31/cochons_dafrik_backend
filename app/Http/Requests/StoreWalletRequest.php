<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'uuid', 'exists:restaurants,id', 'unique:wallets,restaurant_id'],
            'balance_fcfa' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
