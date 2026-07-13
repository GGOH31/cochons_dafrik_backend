<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'is_b2b' => (bool) $this->is_b2b,
            'emojis' => $this->emojis,
            'products_count' => (int) ($this->products_count ?? 0),
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
