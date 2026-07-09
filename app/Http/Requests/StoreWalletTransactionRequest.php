<?php

namespace App\Http\Requests;

use App\Enums\TxType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreWalletTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['nullable', 'uuid', 'exists:wallets,id'],
            'order_id' => ['nullable', 'uuid', 'exists:orders,id'],
            'tx_type' => ['required', new Enum(TxType::class)],
            'amount_fcfa' => ['required', 'integer'],
            'balance_after' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
        ];
    }
}
