<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantCommissionOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'uuid', 'exists:restaurants,id', 'unique:restaurant_commission_overrides,restaurant_id'],
            'rate_pct' => ['required', 'numeric', 'between:0,100'],
            'updated_by' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
