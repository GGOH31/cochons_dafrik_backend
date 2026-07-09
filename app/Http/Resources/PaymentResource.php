<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'provider' => $this->provider?->value,
            'provider_ref' => $this->provider_ref,
            'amount_fcfa' => (int) $this->amount_fcfa,
            'status' => $this->status?->value,
            'payload' => $this->payload,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'order' => new OrderResource($this->whenLoaded('order')),
            'escrow' => new EscrowResource($this->whenLoaded('escrow')),
        ];
    }
}
