<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'balance_fcfa' => (int) $this->balance_fcfa,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'transactions' => WalletTransactionResource::collection($this->whenLoaded('transactions')),
            'withdrawals' => WithdrawalResource::collection($this->whenLoaded('withdrawals')),
        ];
    }
}
