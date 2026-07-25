<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => ['sometimes', 'uuid', 'exists:restaurants,id'],
            'name' => ['sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'photo_url' => ['nullable'],
            'unit' => ['sometimes', 'string', 'max:30'],
            'price_fcfa' => ['sometimes', 'integer', 'min:1'],
            'prep_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'accompaniments' => ['nullable', 'string'],
        ];
    }
}
