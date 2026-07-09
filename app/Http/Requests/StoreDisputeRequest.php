<?php

namespace App\Http\Requests;

use App\Enums\DisputeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'uuid', 'exists:orders,id', 'unique:disputes,order_id'],
            'opened_by' => ['required', 'uuid', 'exists:users,id'],
            'reason' => ['required', 'string'],
            'status' => ['nullable', new Enum(DisputeStatus::class)],
        ];
    }
}
