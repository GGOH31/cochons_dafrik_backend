<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'amount_fcfa' => (int) $this->amount_fcfa,
            'provider' => $this->provider?->value,
            'dest_phone' => $this->dest_phone,
            'status' => $this->status?->value,
            'processed_by' => $this->processed_by,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'processor' => new UserResource($this->whenLoaded('processor')),
        ];
    }
}
