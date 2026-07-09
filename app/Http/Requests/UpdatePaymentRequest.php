<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_ref' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', new Enum(PaymentStatus::class)],
            'payload' => ['nullable', 'array'],
            'paid_at' => ['nullable', 'date'],
            'refunded_at' => ['nullable', 'date'],
        ];
    }
}
