<?php

namespace App\Http\Requests;

use App\Enums\AccountStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'uuid', 'exists:users,id', 'unique:shops,owner_id'],
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string'],
            'logo_file' => ['nullable', 'file', 'image', 'max:5120'],
            'commune' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_open' => ['nullable', 'boolean'],
            'delivery_fee_fcfa' => ['nullable', 'integer', 'min:0'],
            'min_order_fcfa' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', new Enum(AccountStatus::class)],
            'validated_by' => ['nullable', 'uuid', 'exists:users,id'],
            'validated_at' => ['nullable', 'date'],
            'supporting_docs_url' => ['nullable', 'string'],
            'opening_hours' => ['nullable', 'array'],
            'delivery_zone' => ['nullable', 'string'],
        ];
    }
}
