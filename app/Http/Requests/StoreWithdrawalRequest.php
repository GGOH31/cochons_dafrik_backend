<?php

namespace App\Http\Requests;

use App\Enums\PaymentProvider;
use App\Enums\WithdrawalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'uuid', 'exists:wallets,id'],
            'amount_fcfa' => ['required', 'integer', 'min:1'],
            'provider' => ['required', new Enum(PaymentProvider::class)],
            'dest_phone' => ['required', 'string', 'max:20'],
            'status' => ['nullable', new Enum(WithdrawalStatus::class)],
        ];
    }
}
