<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'dish_id' => $this->dish_id,
            'dish_name' => $this->dish_name,
            'unit_price_fcfa' => (int) $this->unit_price_fcfa,
            'quantity' => (float) $this->quantity,
            'options' => $this->options,
            'line_total_fcfa' => (int) $this->line_total_fcfa,
            'order' => new OrderResource($this->whenLoaded('order')),
            'dish' => new DishResource($this->whenLoaded('dish')),
        ];
    }
}
