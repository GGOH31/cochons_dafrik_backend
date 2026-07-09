<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'order_id' => $this->order_id,
            'tx_type' => $this->tx_type?->value,
            'amount_fcfa' => (int) $this->amount_fcfa,
            'balance_after' => $this->balance_after !== null ? (int) $this->balance_after : null,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}
