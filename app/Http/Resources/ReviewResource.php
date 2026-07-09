<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'shop_id' => $this->shop_id,
            'author_id' => $this->author_id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toIso8601String(),
            'order' => new OrderResource($this->whenLoaded('order')),
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'author' => new UserResource($this->whenLoaded('author')),
        ];
    }
}
