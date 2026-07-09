<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', new Enum(OrderStatus::class)],
            'accepted_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
            'confirmed_at' => ['nullable', 'date'],
            'auto_confirm_at' => ['nullable', 'date'],
            'cancel_reason' => ['nullable', 'string'],
        ];
    }
}
