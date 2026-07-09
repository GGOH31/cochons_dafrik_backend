<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShopCommissionOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'uuid', 'exists:shops,id', 'unique:shop_commission_overrides,shop_id'],
            'rate_pct' => ['required', 'numeric', 'between:0,100'],
            'updated_by' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
