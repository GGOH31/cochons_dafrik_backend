<?php

namespace App\Http\Requests;

use App\Enums\DeliveryMode;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:20', 'unique:orders,reference'],
            'order_type' => ['required', new Enum(OrderType::class)],
            'buyer_id' => ['required', 'uuid', 'exists:users,id'],
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'status' => ['nullable', new Enum(OrderStatus::class)],
            'delivery_mode' => ['nullable', new Enum(DeliveryMode::class)],
            'address_id' => ['nullable', 'uuid', 'exists:addresses,id'],
            'delivery_code' => ['nullable', 'string', 'size:4'],
            'subtotal_fcfa' => ['required', 'integer', 'min:0'],
            'delivery_fcfa' => ['nullable', 'integer', 'min:0'],
            'total_fcfa' => ['required', 'integer', 'min:0'],
            'commission_pct' => ['required', 'numeric', 'between:0,100'],
        ];
    }
}
