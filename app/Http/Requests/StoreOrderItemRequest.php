<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'dish_id' => ['required', 'uuid', 'exists:dishes,id'],
            'dish_name' => ['required', 'string', 'max:160'],
            'unit_price_fcfa' => ['required', 'integer', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'options' => ['nullable', 'array'],
            'line_total_fcfa' => ['required', 'integer', 'min:0'],
        ];
    }
}
