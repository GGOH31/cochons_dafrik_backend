<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrderStatusHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'status' => ['required', new Enum(OrderStatus::class)],
            'changed_by' => ['nullable', 'uuid', 'exists:users,id'],
            'note' => ['nullable', 'string'],
        ];
    }
}
