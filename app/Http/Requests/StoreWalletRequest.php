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
            'shop_id' => ['required', 'uuid', 'exists:shops,id', 'unique:wallets,shop_id'],
            'balance_fcfa' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
