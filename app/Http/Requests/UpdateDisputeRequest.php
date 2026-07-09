<?php

namespace App\Http\Requests;

use App\Enums\DisputeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', new Enum(DisputeStatus::class)],
            'resolved_by' => ['nullable', 'uuid', 'exists:users,id'],
            'resolution' => ['nullable', 'string'],
            'resolved_at' => ['nullable', 'date'],
        ];
    }
}
