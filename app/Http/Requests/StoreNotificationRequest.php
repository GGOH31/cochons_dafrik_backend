<?php

namespace App\Http\Requests;

use App\Enums\NotifChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'order_id' => ['nullable', 'uuid', 'exists:orders,id'],
            'channel' => ['required', new Enum(NotifChannel::class)],
            'title' => ['nullable', 'string', 'max:160'],
            'body' => ['required', 'string'],
            'sent_at' => ['nullable', 'date'],
            'error' => ['nullable', 'string'],
        ];
    }
}
