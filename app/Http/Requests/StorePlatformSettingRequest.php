<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlatformSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:60', 'unique:platform_settings,key'],
            'value' => ['required', 'array'],
            'updated_by' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
