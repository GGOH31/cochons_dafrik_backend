<?php

namespace App\Http\Requests;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'provider' => ['required', new Enum(PaymentProvider::class)],
            'provider_ref' => ['nullable', 'string', 'max:120'],
            'amount_fcfa' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', new Enum(PaymentStatus::class)],
            'payload' => ['nullable', 'array'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
