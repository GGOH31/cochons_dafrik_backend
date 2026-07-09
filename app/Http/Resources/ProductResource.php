<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'photo_url' => $this->photo_url,
            'unit' => $this->unit,
            'price_fcfa' => (int) $this->price_fcfa,
            'prep_minutes' => $this->prep_minutes ? (int) $this->prep_minutes : null,
            'stock_qty' => $this->stock_qty !== null ? (int) $this->stock_qty : null,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'promotions' => PromotionResource::collection($this->whenLoaded('promotions')),
        ];
    }
}
