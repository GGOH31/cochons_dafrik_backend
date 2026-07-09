<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EscrowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'payment_id' => $this->payment_id,
            'amount_fcfa' => (int) $this->amount_fcfa,
            'status' => $this->status?->value,
            'held_at' => $this->held_at?->toIso8601String(),
            'released_at' => $this->released_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'order' => new OrderResource($this->whenLoaded('order')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
        ];
    }
}
