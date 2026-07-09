<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'status' => $this->status?->value,
            'changed_by' => $this->changed_by,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
            'order' => new OrderResource($this->whenLoaded('order')),
            'changer' => new UserResource($this->whenLoaded('changer')),
        ];
    }
}
