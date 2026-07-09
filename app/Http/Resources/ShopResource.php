<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'commune' => $this->commune,
            'address' => $this->address,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'is_open' => (bool) $this->is_open,
            'delivery_fee_fcfa' => (int) $this->delivery_fee_fcfa,
            'min_order_fcfa' => (int) $this->min_order_fcfa,
            'status' => $this->status?->value,
            'validated_by' => $this->validated_by,
            'validated_at' => $this->validated_at?->toIso8601String(),
            'rating_avg' => $this->rating_avg ? (float) $this->rating_avg : 0.0,
            'rating_count' => (int) $this->rating_count,
            'supporting_docs_url' => $this->supporting_docs_url,
            'opening_hours' => $this->opening_hours,
            'delivery_zone' => $this->delivery_zone,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'commission_override' => new ShopCommissionOverrideResource($this->whenLoaded('commissionOverride')),
        ];
    }
}
