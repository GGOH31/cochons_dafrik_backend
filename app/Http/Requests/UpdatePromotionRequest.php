<?php

namespace App\Http\Requests;

use App\Enums\PromoType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['sometimes', 'uuid', 'exists:shops,id'],
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'title' => ['sometimes', 'string', 'max:140'],
            'promo_type' => ['sometimes', new Enum(PromoType::class)],
            'value' => ['sometimes', 'integer', 'min:1'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
