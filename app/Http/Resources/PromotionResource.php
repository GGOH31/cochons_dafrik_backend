<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'dish_id' => $this->dish_id,
            'title' => $this->title,
            'promo_type' => $this->promo_type?->value,
            'value' => (int) $this->value,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'dish' => new DishResource($this->whenLoaded('dish')),
        ];
    }
}
