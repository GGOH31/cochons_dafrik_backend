<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', 'unique:categories,name'],
            'is_b2b' => ['nullable', 'boolean'],
            'emojis' => ['nullable', 'string'],
        ];
    }
}
