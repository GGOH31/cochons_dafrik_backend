<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'updater' => new UserResource($this->whenLoaded('updater')),
        ];
    }
}
