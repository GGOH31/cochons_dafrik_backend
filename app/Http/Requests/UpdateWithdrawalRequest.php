<?php

namespace App\Http\Requests;

use App\Enums\WithdrawalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', new Enum(WithdrawalStatus::class)],
            'processed_by' => ['nullable', 'uuid', 'exists:users,id'],
            'processed_at' => ['nullable', 'date'],
        ];
    }
}
