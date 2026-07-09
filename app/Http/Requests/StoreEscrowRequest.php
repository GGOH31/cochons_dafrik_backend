<?php

namespace App\Http\Requests;

use App\Enums\EscrowStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreEscrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'uuid', 'exists:orders,id', 'unique:escrows,order_id'],
            'payment_id' => ['required', 'uuid', 'exists:payments,id'],
            'amount_fcfa' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', new Enum(EscrowStatus::class)],
            'released_at' => ['nullable', 'date'],
            'refunded_at' => ['nullable', 'date'],
        ];
    }
}
