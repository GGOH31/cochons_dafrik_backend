<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'order_type' => $this->order_type?->value,
            'buyer_id' => $this->buyer_id,
            'shop_id' => $this->shop_id,
            'status' => $this->status?->value,
            'delivery_mode' => $this->delivery_mode?->value,
            'address_id' => $this->address_id,
            'delivery_code' => $this->delivery_code,
            'subtotal_fcfa' => (int) $this->subtotal_fcfa,
            'delivery_fcfa' => (int) $this->delivery_fcfa,
            'total_fcfa' => (int) $this->total_fcfa,
            'commission_pct' => $this->commission_pct ? (float) $this->commission_pct : 0.0,
            'commission_fcfa' => (int) $this->commission_fcfa,
            'seller_net_fcfa' => (int) $this->seller_net_fcfa,
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'auto_confirm_at' => $this->auto_confirm_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'buyer' => new UserResource($this->whenLoaded('buyer')),
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'address' => new AddressResource($this->whenLoaded('address')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'escrow' => new EscrowResource($this->whenLoaded('escrow')),
            'dispute' => new DisputeResource($this->whenLoaded('dispute')),
            'review' => new ReviewResource($this->whenLoaded('review')),
        ];
    }
}
