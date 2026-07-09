<?php

namespace App\Http\Requests;

use App\Enums\AccountStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shopId = $this->route('shop')?->id ?? $this->route('shop');

        return [
            'owner_id' => ['sometimes', 'uuid', 'exists:users,id', 'unique:shops,owner_id,' . $shopId],
            'name' => ['sometimes', 'string', 'max:140'],
            'description' => ['nullable', 'string'],
            'logo_url' => ['nullable'],
            'commune' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_open' => ['sometimes', 'boolean'],
            'delivery_fee_fcfa' => ['sometimes', 'integer', 'min:0'],
            'min_order_fcfa' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', new Enum(AccountStatus::class)],
            'validated_by' => ['nullable', 'uuid', 'exists:users,id'],
            'validated_at' => ['nullable', 'date'],
            'supporting_docs_url' => ['nullable', 'string'],
            'opening_hours' => ['nullable', 'array'],
            'delivery_zone' => ['nullable', 'string'],
        ];
    }
}
