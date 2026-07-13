<?php

namespace App\Services;

use App\Models\User;
use App\Models\Shop;
use App\Models\Wallet;
use App\Models\Category;
use App\Models\Product;
use App\Models\Accompaniment;
use App\Models\Promotion;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Escrow;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use App\Models\PlatformSetting;
use App\Enums\AccountStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\EscrowStatus;
use App\Enums\TxType;
use App\Enums\WithdrawalStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VendeurService
{
    /**
     * Create a shop and its associated wallet for the vendor.
     */
    public function createShop(User $user, array $data): Shop
    {
        if ($user->shop) {
            throw new \Exception('Cet utilisateur possède déjà une boutique.');
        }

        return DB::transaction(function () use ($user, $data) {
            $logoUrl = null;
            $supportingDocsUrl = null;

            if (!empty($data['logo_file']) && $data['logo_file'] instanceof \Illuminate\Http\UploadedFile) {
                $uploaded = CloudinaryService::uploadImage($data['logo_file'], 'shops');
                $logoUrl = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
            } elseif (!empty($data['logo_url'])) {
                $logoUrl = $data['logo_url'];
            }

            if (!empty($data['supporting_docs_file']) && $data['supporting_docs_file'] instanceof \Illuminate\Http\UploadedFile) {
                $uploaded = CloudinaryService::uploadImage($data['supporting_docs_file'], 'shops');
                $supportingDocsUrl = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
            } elseif (!empty($data['supporting_docs_url'])) {
                $supportingDocsUrl = $data['supporting_docs_url'];
            }

            $shop = Shop::create([
                'owner_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'logo_url' => $logoUrl,
                'commune' => $data['commune'] ?? null,
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'is_open' => $data['is_open'] ?? false,
                'delivery_fee_fcfa' => $data['delivery_fee_fcfa'] ?? 0,
                'min_order_fcfa' => $data['min_order_fcfa'] ?? 0,
                'status' => AccountStatus::PENDING,
                'supporting_docs_url' => $supportingDocsUrl,
                'opening_hours' => $data['opening_hours'] ?? null,
                'delivery_zone' => $data['delivery_zone'] ?? null,
            ]);

            // Create wallet for the shop
            Wallet::create([
                'shop_id' => $shop->id,
                'balance_fcfa' => 0,
            ]);

            return $shop;
        });
    }

    /**
     * Update personal and shop profiles.
     */
    public function updateProfile(User $user, array $userData, array $shopData = []): User
    {
        return DB::transaction(function () use ($user, $userData, $shopData) {
            $userUpdate = [];
            if (isset($userData['full_name'])) {
                $userUpdate['full_name'] = $userData['full_name'];
            }
            if (isset($userData['email'])) {
                $userUpdate['email'] = $userData['email'];
            }
            if (isset($userData['password'])) {
                $userUpdate['password_hash'] = Hash::make($userData['password']);
            }
            if (isset($userData['fcm_token'])) {
                $userUpdate['fcm_token'] = $userData['fcm_token'];
            }

            if (!empty($userUpdate)) {
                $user->update($userUpdate);
            }

            if (!empty($shopData) && $user->shop) {
                $user->shop->update($shopData);
            }

            return $user->load('shop');
        });
    }

    /**
     * Create a category.
     */
    public function createCategory(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Update a category.
     */
    public function updateCategory(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);
        $category->update($data);
        return $category;
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(int $id): bool
    {
        $category = Category::findOrFail($id);
        return $category->delete();
    }

    /**
     * Create a product.
     */
    public function createProduct(array $data): Product
    {
        if (!empty($data['photo_file']) && $data['photo_file'] instanceof \Illuminate\Http\UploadedFile) {
            $uploaded = CloudinaryService::uploadImage($data['photo_file'], 'products');
            $data['photo_url'] = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
            unset($data['photo_file']);
        }

        return Product::create($data);
    }

    /**
     * Update a product.
     */
    public function updateProduct(string $productId, array $data, string $shopId): Product
    {
        if (isset($data['accompaniments'])) {
            unset($data['accompaniments']);
        }

        $product = Product::where('id', $productId)->where('shop_id', $shopId)->firstOrFail();
        $product->update($data);

        return $product;
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(string $productId, string $shopId): bool
    {
        $product = Product::where('id', $productId)->where('shop_id', $shopId)->firstOrFail();
        return $product->delete();
    }

    /**
     * Accept a paid B2C order.
     */
    public function acceptOrder(string $orderId, string $shopId): Order
    {
        $order = Order::where('id', $orderId)->where('shop_id', $shopId)->firstOrFail();

        if ($order->status !== OrderStatus::PAID) {
            throw new \Exception('Seules les commandes payées peuvent être acceptées.');
        }

        $order->update([
            'status' => OrderStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);

        // Notify client
        $buyer = $order->buyer;
        if ($buyer) {
            $msg = "Votre commande {$order->reference} a été acceptée par le vendeur et est en préparation.";
            if ($buyer->fcm_token) {
                app(FirebasePushService::class)->sendPush($buyer->fcm_token, "Commande Acceptée", $msg);
            }
            app(SmsPushService::class)->sendSms($buyer->phone, $msg);
        }

        return $order;
    }

    /**
     * Refuse a paid B2C order (triggers automatic refund and restocking).
     */
    public function refuseOrder(string $orderId, string $shopId): Order
    {
        $order = Order::where('id', $orderId)->where('shop_id', $shopId)->with('items.product')->firstOrFail();

        if ($order->status !== OrderStatus::PAID && $order->status !== OrderStatus::PENDING_PAYMENT) {
            throw new \Exception('Seules les commandes non encore traitées ou payées peuvent être refusées.');
        }

        return DB::transaction(function () use ($order) {
            // Restock items
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product && $product->stock_qty !== null) {
                    $product->increment('stock_qty', $item->quantity);
                }
            }

            // Refund payment (simulation)
            Payment::where('order_id', $order->id)->update([
                'status' => PaymentStatus::REFUNDED,
                'refunded_at' => now(),
            ]);

            // Refund escrow
            Escrow::where('order_id', $order->id)->update([
                'status' => EscrowStatus::REFUNDED,
                'refunded_at' => now(),
            ]);

            $order->update([
                'status' => OrderStatus::REFUSED,
            ]);

            // Notify client
            $buyer = $order->buyer;
            if ($buyer) {
                $msg = "Votre commande {$order->reference} a été refusée par le vendeur. Vous avez été remboursé sur votre Mobile Money.";
                if ($buyer->fcm_token) {
                    app(FirebasePushService::class)->sendPush($buyer->fcm_token, "Commande Refusée", $msg);
                }
                app(SmsPushService::class)->sendSms($buyer->phone, $msg);
            }

            return $order;
        });
    }

    /**
     * Progress order status: preparing -> delivering -> delivered.
     */
    public function updateOrderStatus(string $orderId, string $status, string $shopId): Order
    {
        $order = Order::where('id', $orderId)->where('shop_id', $shopId)->firstOrFail();

        // Validate state transitions
        if ($status === OrderStatus::PREPARING->value) {
            if ($order->status !== OrderStatus::ACCEPTED) {
                throw new \Exception('La commande doit être acceptée avant de passer en préparation.');
            }
        } elseif ($status === OrderStatus::DELIVERING->value) {
            if ($order->status !== OrderStatus::PREPARING) {
                throw new \Exception('La commande doit être en préparation avant de passer en livraison.');
            }
        } elseif ($status === OrderStatus::DELIVERED->value) {
            if ($order->status !== OrderStatus::DELIVERING) {
                throw new \Exception('La commande doit être en cours de livraison avant de passer à livrée.');
            }
        } else {
            throw new \Exception('Statut de transition invalide.');
        }

        $updateData = ['status' => $status];

        if ($status === OrderStatus::DELIVERED->value) {
            $updateData['delivered_at'] = now();
            // Fetch platform setting auto_confirm_delay_hours
            $delayHours = (int) (PlatformSetting::where('key', 'auto_confirm_delay_hours')->first()?->value ?? 24);
            $updateData['auto_confirm_at'] = now()->addHours($delayHours);
        }

        $order->update($updateData);

        // Notify client
        $buyer = $order->buyer;
        if ($buyer) {
            $statusText = match ($status) {
                OrderStatus::PREPARING->value => 'est en cours de préparation',
                OrderStatus::DELIVERING->value => 'est en cours de livraison',
                OrderStatus::DELIVERED->value => 'a été livrée. Confirmez la réception pour finaliser la commande',
                default => 'a changé de statut',
            };
            $msg = "Votre commande {$order->reference} {$statusText}.";
            if ($buyer->fcm_token) {
                app(FirebasePushService::class)->sendPush($buyer->fcm_token, "Statut Commande", $msg);
            }
            app(SmsPushService::class)->sendSms($buyer->phone, $msg);
        }

        return $order;
    }

    /**
     * Request a withdrawal from shop wallet to Mobile Money.
     */
    public function requestWithdrawal(string $shopId, int $amount, string $provider, string $destPhone): Withdrawal
    {
        $wallet = Wallet::where('shop_id', $shopId)->firstOrFail();

        // Fetch platform min withdrawal amount
        $minWithdrawal = (int) (PlatformSetting::where('key', 'min_withdrawal_fcfa')->first()?->value ?? 5000);
        if ($amount < $minWithdrawal) {
            throw new \Exception("Le montant minimal de retrait est de {$minWithdrawal} FCFA.");
        }

        if ($wallet->balance_fcfa < $amount) {
            throw new \Exception('Solde du portefeuille insuffisant.');
        }

        return DB::transaction(function () use ($wallet, $amount, $provider, $destPhone) {
            // Debit wallet balance
            $wallet->decrement('balance_fcfa', $amount);

            // Create withdrawal request
            $withdrawal = Withdrawal::create([
                'wallet_id' => $wallet->id,
                'amount_fcfa' => $amount,
                'provider' => $provider,
                'dest_phone' => $destPhone,
                'status' => WithdrawalStatus::PENDING,
            ]);

            // Add transaction in wallet ledger
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'tx_type' => TxType::WITHDRAWAL,
                'amount_fcfa' => -$amount,
                'balance_after' => $wallet->balance_fcfa,
                'note' => "Demande de retrait Mobile Money ({$provider}) vers {$destPhone}",
            ]);

            return $withdrawal;
        });
    }

    /**
     * Get all accompaniments for products of a specific shop.
     */
    public function getAccompaniments(string $shopId): \Illuminate\Database\Eloquent\Collection
    {
        $productIds = Product::where('shop_id', $shopId)->pluck('id');
        return Accompaniment::whereIn('product_id', $productIds)->with('product')->get();
    }

    /**
     * Create an accompaniment.
     */
    public function createAccompaniment(array $data): Accompaniment
    {
        if (!empty($data['photo_file']) && $data['photo_file'] instanceof \Illuminate\Http\UploadedFile) {
            $uploaded = CloudinaryService::uploadImage($data['photo_file'], 'accompaniments');
            $data['photo_url'] = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
            unset($data['photo_file']);
        }

        return Accompaniment::create($data);
    }

    /**
     * Update an accompaniment.
     */
    public function updateAccompaniment(int $id, array $data): Accompaniment
    {
        $accompaniment = Accompaniment::findOrFail($id);

        if (!empty($data['photo_file']) && $data['photo_file'] instanceof \Illuminate\Http\UploadedFile) {
            $uploaded = CloudinaryService::uploadImage($data['photo_file'], 'accompaniments');
            $data['photo_url'] = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
            unset($data['photo_file']);
        }

        $accompaniment->update($data);
        return $accompaniment;
    }

    /**
     * Delete an accompaniment.
     */
    public function deleteAccompaniment(int $id): bool
    {
        $accompaniment = Accompaniment::findOrFail($id);
        return $accompaniment->delete();
    }

    /**
     * Get promotions for a specific shop.
     */
    public function getPromotions(string $shopId): \Illuminate\Database\Eloquent\Collection
    {
        return Promotion::where('shop_id', $shopId)->with('product')->get();
    }

    /**
     * Create a promotion.
     */
    public function createPromotion(array $data): Promotion
    {
        return Promotion::create($data);
    }

    /**
     * Update a promotion.
     */
    public function updatePromotion(string $id, array $data): Promotion
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->update($data);
        return $promotion;
    }

    /**
     * Delete a promotion.
     */
    public function deletePromotion(string $id): bool
    {
        $promotion = Promotion::findOrFail($id);
        return $promotion->delete();
    }
}
