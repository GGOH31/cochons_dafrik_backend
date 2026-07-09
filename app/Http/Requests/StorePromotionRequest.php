<?php

namespace App\Http\Requests;

use App\Enums\PromoType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'title' => ['required', 'string', 'max:140'],
            'promo_type' => ['required', new Enum(PromoType::class)],
            'value' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
