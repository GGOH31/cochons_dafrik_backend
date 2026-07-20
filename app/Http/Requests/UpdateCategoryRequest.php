<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id') ?? $this->route('category')?->id ?? $this->route('category');
        $shopId = $this->user()?->shop?->id;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:80',
                Rule::unique('categories', 'name')
                    ->ignore($categoryId)
                    ->where(function ($query) use ($shopId) {
                        return $query->where('shop_id', $shopId);
                    }),
            ],
            'is_b2b' => ['sometimes', 'boolean'],
            'emojis' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
