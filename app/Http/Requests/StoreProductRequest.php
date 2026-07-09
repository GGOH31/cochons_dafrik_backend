<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'photo_file' => ['nullable', 'file', 'image', 'max:5120'],
            'unit' => ['required', 'string', 'max:30'],
            'price_fcfa' => ['required', 'integer', 'min:1'],
            'prep_minutes' => ['nullable', 'integer', 'min:1'],
            'stock_qty' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
