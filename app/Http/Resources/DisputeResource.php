<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'opened_by' => $this->opened_by,
            'reason' => $this->reason,
            'status' => $this->status?->value,
            'resolved_by' => $this->resolved_by,
            'resolution' => $this->resolution,
            'created_at' => $this->created_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'order' => new OrderResource($this->whenLoaded('order')),
            'opener' => new UserResource($this->whenLoaded('opener')),
            'resolver' => new UserResource($this->whenLoaded('resolver')),
        ];
    }
}
