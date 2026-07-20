<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shopId = $this->user()?->shop?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('categories', 'name')->where(function ($query) use ($shopId) {
                    return $query->where('shop_id', $shopId);
                }),
            ],
            'is_b2b' => ['nullable', 'boolean'],
            'emojis' => ['nullable', 'string'],
        ];
    }
}
