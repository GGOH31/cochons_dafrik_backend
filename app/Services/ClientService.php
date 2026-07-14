<?php

namespace App\Services;

use App\Models\User;
use App\Models\Address;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Escrow;
use App\Models\Review;
use App\Enums\AccountStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\EscrowStatus;
use App\Enums\DeliveryMode;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientService
{
    /**
     * Save a new delivery address for the client.
     */
    public function saveAddress(User $user, array $data): Address
    {
        if (!empty($data['is_default'])) {
            Address::where('user_id', $user->id)->update(['is_default' => false]);
        }

        return Address::create(array_merge($data, ['user_id' => $user->id]));
    }

    /**
     * Search active shops with optional filters and GPS geo-localization.
     */
    public function searchShops(array $filters): Collection
    {
        $query = Shop::where('status', AccountStatus::ACTIVE);

        if (!empty($filters['name'])) {
            $query->where('name', 'ILIKE', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['commune'])) {
            $query->where('commune', 'ILIKE', '%' . $filters['commune'] . '%');
        }

        if (isset($filters['is_open'])) {
            $query->where('is_open', (bool) $filters['is_open']);
        }

        // Proximity geo-localization sorting using SQL Haversine formula
        if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
            $userLat = (float) $filters['latitude'];
            $userLng = (float) $filters['longitude'];

            $query->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [$userLat, $userLng, $userLat])
                  ->orderBy('distance');

            if (!empty($filters['max_distance'])) {
                $query->having('distance', '<=', (float) $filters['max_distance']);
            }
        }

        return $query->get();
    }

    /**
     * List products of a shop with optional filters.
     */
    public function getShopProducts(string $shopId, array $filters): Collection
    {
        Shop::where('id', $shopId)->where('status', AccountStatus::ACTIVE)->firstOrFail();

        $query = Product::where('shop_id', $shopId)
            ->where('is_active', true)
            ->with(['shop', 'category', 'accompaniments']);

        if (!empty($filters['name'])) {
            $query->where('name', 'ILIKE', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['price_min'])) {
            $query->where('price_fcfa', '>=', (int) $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price_fcfa', '<=', (int) $filters['price_max']);
        }

        return $query->get();
    }

    /**
     * Create a new multi-product order.
     */
    public function createOrder(User $buyer, array $data): Order
    {
        $shopId = $data['shop_id'];
        $items = $data['items'];

        $shop = Shop::where('id', $shopId)->where('status', AccountStatus::ACTIVE)->firstOrFail();

        return DB::transaction(function () use ($buyer, $shop, $data, $items) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                $product = Product::where('id', $item['product_id'])
                                  ->where('shop_id', $shop->id)
                                  ->where('is_active', true)
                                  ->firstOrFail();

                if ($product->stock_qty !== null) {
                    if ($product->stock_qty < $item['quantity']) {
                        throw new \Exception("Stock insuffisant pour le produit: {$product->name}");
                    }
                    $product->decrement('stock_qty', $item['quantity']);
                }

                $lineTotal = (int) round($product->price_fcfa * $item['quantity']);
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price_fcfa' => $product->price_fcfa,
                    'quantity' => $item['quantity'],
                    'options' => $item['options'] ?? null,
                    'line_total_fcfa' => $lineTotal,
                ];
            }

            $deliveryFee = 0;
            if ($data['delivery_mode'] === DeliveryMode::DELIVERY->value) {
                $deliveryFee = $shop->delivery_fee_fcfa;
            }

            $total = $subtotal + $deliveryFee;
            $reference = ($data['order_type'] === OrderType::B2B->value ? 'B2B-' : 'CDA-') . strtoupper(Str::random(8));

            // Note: Database trigger "orders_set_commission" automatically populates commission rates, net amount, etc.
            $order = Order::create([
                'reference' => $reference,
                'order_type' => $data['order_type'],
                'buyer_id' => $buyer->id,
                'shop_id' => $shop->id,
                'status' => OrderStatus::PENDING_PAYMENT,
                'delivery_mode' => $data['delivery_mode'],
                'address_id' => $data['address_id'] ?? null,
                'delivery_code' => str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'subtotal_fcfa' => $subtotal,
                'delivery_fcfa' => $deliveryFee,
                'total_fcfa' => $total,
                'commission_pct' => 0,
                'commission_fcfa' => 0,
                'seller_net_fcfa' => 0,
            ]);

            foreach ($itemsData as $itemData) {
                $order->items()->create($itemData);
            }

            return $order->load('items');
        });
    }

    /**
     * Simulate payment callback, holding escrow funds.
     */
    public function payOrder(string $orderId, string $provider, string $providerRef): Order
    {
        $order = Order::findOrFail($orderId);

        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            throw new \Exception('Cette commande a déjà été payée ou n\'est pas en attente de paiement.');
        }

        return DB::transaction(function () use ($order, $provider, $providerRef) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => $provider,
                'provider_ref' => $providerRef,
                'amount_fcfa' => $order->total_fcfa,
                'status' => PaymentStatus::SUCCESS,
                'paid_at' => now(),
            ]);

            Escrow::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount_fcfa' => $order->total_fcfa,
                'status' => EscrowStatus::HELD,
                'held_at' => now(),
            ]);

            $order->update([
                'status' => OrderStatus::PAID,
            ]);

            // Notify shop owner
            $owner = $order->shop->owner;
            if ($owner) {
                $msg = "Nouvelle commande {$order->reference} reçue ! Montant: {$order->total_fcfa} FCFA.";
                if ($owner->fcm_token) {
                    app(FirebasePushService::class)->sendPush($owner->fcm_token, "Nouvelle Commande", $msg);
                }
                app(SmsPushService::class)->sendSms($owner->phone, $msg);
            }

            return $order->load(['payments', 'escrow']);
        });
    }

    /**
     * Confirm reception of a delivered order (releases escrow funds to vendor wallet balance).
     */
    public function confirmReception(string $orderId, User $buyer): Order
    {
        $order = Order::where('id', $orderId)->where('buyer_id', $buyer->id)->firstOrFail();

        if ($order->status !== OrderStatus::DELIVERED) {
            throw new \Exception('Seules les commandes livrées peuvent être confirmées.');
        }

        DB::transaction(function () use ($order, $buyer) {
            DB::statement('SELECT release_escrow(?, ?)', [$order->id, $buyer->id]);
        });

        $order->refresh();

        // Notify vendor
        $owner = $order->shop->owner;
        if ($owner) {
            $msg = "Le client a confirmé la réception de la commande {$order->reference}. Les fonds ont été libérés dans votre portefeuille.";
            if ($owner->fcm_token) {
                app(FirebasePushService::class)->sendPush($owner->fcm_token, "Séquestre Libéré", $msg);
            }
            app(SmsPushService::class)->sendSms($owner->phone, $msg);
        }

        return $order;
    }

    /**
     * Submit shop review and rating after order completion.
     */
    public function submitReview(string $orderId, User $buyer, array $data): Review
    {
        $order = Order::where('id', $orderId)->where('buyer_id', $buyer->id)->firstOrFail();

        if ($order->status !== OrderStatus::COMPLETED) {
            throw new \Exception('Vous ne pouvez noter qu\'une commande terminée.');
        }

        return Review::create([
            'order_id' => $order->id,
            'shop_id' => $order->shop_id,
            'author_id' => $buyer->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }

    /**
     * Re-order items of a past order.
     */
    public function reorder(string $orderId, User $buyer): Order
    {
        $oldOrder = Order::where('id', $orderId)->where('buyer_id', $buyer->id)->with('items')->firstOrFail();

        $items = [];
        foreach ($oldOrder->items as $item) {
            $items[] = [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'options' => $item->options,
            ];
        }

        return $this->createOrder($buyer, [
            'shop_id' => $oldOrder->shop_id,
            'order_type' => $oldOrder->order_type->value,
            'delivery_mode' => $oldOrder->delivery_mode->value,
            'address_id' => $oldOrder->address_id,
            'items' => $items,
        ]);
    }

    /**
     * Search products by name across all shops.
     */
    public function searchProducts(array $filters): Collection
    {
        $query = Product::where('is_active', true)->with(['shop', 'category', 'accompaniments']);

        if (!empty($filters['name'])) {
            $query->where('name', 'ILIKE', '%' . $filters['name'] . '%');
        }

        return $query->get();
    }
}
