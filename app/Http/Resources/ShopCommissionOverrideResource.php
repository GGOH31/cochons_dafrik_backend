<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopCommissionOverrideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'shop_id' => $this->shop_id,
            'rate_pct' => $this->rate_pct ? (float) $this->rate_pct : 0.0,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'updater' => new UserResource($this->whenLoaded('updater')),
        ];
    }
}
