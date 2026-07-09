<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopCommissionOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rate_pct' => ['required', 'numeric', 'between:0,100'],
            'updated_by' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
