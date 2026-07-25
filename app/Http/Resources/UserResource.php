<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role?->value,
            'phone' => $this->phone,
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'full_name' => $this->full_name,
            'email' => $this->email,
            'fcm_token' => $this->fcm_token,
            'status' => $this->status?->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
        ];
    }
}
