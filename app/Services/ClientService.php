<?php

namespace App\Services;

use App\Models\User;
use App\Models\Address;
use App\Models\Restaurant;
use App\Models\Dish;
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
     * Search active restaurants with optional filters and GPS geo-localization.
     */
    public function searchRestaurants(array $filters): Collection
    {
        $query = Restaurant::where('status', AccountStatus::ACTIVE);

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
     * List dishes of a shop with optional filters.
     */
    public function getShopDishes(string $restaurantId, array $filters): Collection
    {
        Restaurant::where('id', $restaurantId)->where('status', AccountStatus::ACTIVE)->firstOrFail();

        $query = Dish::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['restaurant', 'accompaniments']);

        if (!empty($filters['name'])) {
            $query->where('name', 'ILIKE', '%' . $filters['name'] . '%');
        }



        if (isset($filters['price_min'])) {
            $query->where('price_fcfa', '>=', (int) $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price_fcfa', '<=', (int) $filters['price_max']);
        }

        return $query->with('restaurant')->get();
    }

    /**
     * Create a new multi-product order.
     */
    public function createOrder(User $buyer, array $data): Order
    {
        $restaurantId = $data['restaurant_id'];
        $items = $data['items'];

        $restaurant = Restaurant::where('id', $restaurantId)->where('status', AccountStatus::ACTIVE)->firstOrFail();

        return DB::transaction(function () use ($buyer, $restaurant, $data, $items) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                $dish = Dish::where('id', $item['dish_id'])
                                  ->where('restaurant_id', $restaurant->id)
                                  ->where('is_active', true)
                                  ->firstOrFail();



                $lineTotal = (int) round($dish->price_fcfa * $item['quantity']);
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'dish_id' => $dish->id,
                    'dish_name' => $dish->name,
                    'unit_price_fcfa' => $dish->price_fcfa,
                    'quantity' => $item['quantity'],
                    'options' => $item['options'] ?? null,
                    'line_total_fcfa' => $lineTotal,
                ];
            }

            $deliveryFee = 0;
            if ($data['delivery_mode'] === DeliveryMode::DELIVERY->value) {
                $deliveryFee = $restaurant->delivery_fee_fcfa;
            }

            $total = $subtotal + $deliveryFee;
            $reference = ($data['order_type'] === OrderType::B2B->value ? 'B2B-' : 'CDA-') . strtoupper(Str::random(8));

            // Note: Database trigger "orders_set_commission" automatically populates commission rates, net amount, etc.
            $order = Order::create([
                'reference' => $reference,
                'order_type' => $data['order_type'],
                'buyer_id' => $buyer->id,
                'restaurant_id' => $restaurant->id,
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
    public function payOrder(string $orderId, string $provider, string $providerRef, ?string $paymentMethodId = null): Order
    {
        $order = Order::findOrFail($orderId);

        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            throw new \Exception('Cette commande a déjà été payée ou n\'est pas en attente de paiement.');
        }

        return DB::transaction(function () use ($order, $provider, $providerRef, $paymentMethodId) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethodId,
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
            $owner = $order->restaurant->owner;
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

        if (!in_array($order->status, [OrderStatus::DELIVERED, OrderStatus::DELIVERING])) {
            throw new \Exception('Seules les commandes en cours de livraison ou livrées peuvent être confirmées.');
        }

        if ($order->status === OrderStatus::DELIVERING) {
            $order->status = OrderStatus::DELIVERED;
            $order->delivered_at = now();
            $order->save();
        }

        DB::transaction(function () use ($order, $buyer) {
            DB::statement('SELECT release_escrow(?, ?)', [$order->id, $buyer->id]);
        });

        $order->refresh();

        // Notify vendor
        $owner = $order->restaurant->owner;
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
            'restaurant_id' => $order->restaurant_id,
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
                'dish_id' => $item->dish_id,
                'quantity' => $item->quantity,
                'options' => $item->options,
            ];
        }

        return $this->createOrder($buyer, [
            'restaurant_id' => $oldOrder->restaurant_id,
            'order_type' => $oldOrder->order_type->value,
            'delivery_mode' => $oldOrder->delivery_mode->value,
            'address_id' => $oldOrder->address_id,
            'items' => $items,
        ]);
    }

    /**
     * Search dishes by name across all restaurants.
     */
    public function searchDishes(array $filters): Collection
    {
        $query = Dish::where('is_active', true)->with(['restaurant', 'accompaniments']);

        if (!empty($filters['name'])) {
            $query->where('name', 'ILIKE', '%' . $filters['name'] . '%');
        }

        return $query->get();
    }
}
