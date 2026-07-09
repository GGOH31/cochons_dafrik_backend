<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\ShopCommissionOverride;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Dispute;
use App\Models\Escrow;
use App\Models\Payment;
use App\Models\Order;
use App\Enums\AccountStatus;
use App\Enums\DisputeStatus;
use App\Enums\EscrowStatus;
use App\Enums\PaymentStatus;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;

class AdminService
{
    /**
     * Validate a shop status (active / rejected).
     */
    public function validateShop(string $shopId, string $adminId, string $status): Shop
    {
        $shop = Shop::findOrFail($shopId);

        $shop->update([
            'status' => $status,
            'validated_by' => $adminId,
            'validated_at' => now(),
        ]);

        return $shop;
    }

    /**
     * Update commission rate override for a shop.
     */
    public function updateShopCommission(string $shopId, float $ratePct, string $adminId): ShopCommissionOverride
    {
        return ShopCommissionOverride::updateOrCreate(
            ['shop_id' => $shopId],
            [
                'rate_pct' => $ratePct,
                'updated_by' => $adminId,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Update global platform commission settings.
     */
    public function updatePlatformSetting(string $key, $value, string $adminId): PlatformSetting
    {
        $setting = PlatformSetting::findOrFail($key);
        $setting->update([
            'value' => $value,
            'updated_by' => $adminId,
            'updated_at' => now(),
        ]);

        return $setting;
    }

    /**
     * Retrieve platform dashboard stats.
     */
    public function getDashboardStats(): array
    {
        $stats = DB::table('v_admin_dashboard')->first();
        $activeUsers = User::where('status', AccountStatus::ACTIVE)->count();

        return [
            'commandes_terminees' => (int) ($stats->commandes_terminees ?? 0),
            'commandes_en_cours' => (int) ($stats->commandes_en_cours ?? 0),
            'litiges_ouverts' => (int) ($stats->litiges_ouverts ?? 0),
            'volume_fcfa' => (int) ($stats->volume_fcfa ?? 0),
            'commissions_fcfa' => (int) ($stats->commissions_fcfa ?? 0),
            'utilisateurs_actifs' => $activeUsers,
        ];
    }

    /**
     * Retrieve active escrows supervision details.
     */
    public function getEscrows(): array
    {
        return DB::table('v_escrow_en_cours')->get()->toArray();
    }

    /**
     * Resolve an open dispute.
     */
    public function resolveDispute(string $disputeId, string $adminId, string $action, string $resolutionText): Dispute
    {
        $dispute = Dispute::findOrFail($disputeId);

        if ($dispute->status !== DisputeStatus::OPEN) {
            throw new \Exception('Ce litige a déjà été résolu.');
        }

        return DB::transaction(function () use ($dispute, $adminId, $action, $resolutionText) {
            $order = $dispute->order;

            if ($action === 'refund') {
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

                // Update order status
                $order->update([
                    'status' => OrderStatus::CANCELLED,
                ]);

                // Update dispute
                $dispute->update([
                    'status' => DisputeStatus::RESOLVED_REFUND,
                    'resolved_by' => $adminId,
                    'resolution' => $resolutionText,
                    'resolved_at' => now(),
                ]);

                // Notify buyer
                $buyer = $order->buyer;
                if ($buyer) {
                    $msg = "Le litige concernant votre commande {$order->reference} a été résolu en votre faveur. Vous avez été remboursé.";
                    if ($buyer->fcm_token) {
                        app(FirebasePushService::class)->sendPush($buyer->fcm_token, "Litige Résolu", $msg);
                    }
                    app(SmsPushService::class)->sendSms($buyer->phone, $msg);
                }

            } elseif ($action === 'release') {
                // Call standard stored procedure to release escrow
                DB::statement('SELECT release_escrow(?, ?)', [$order->id, $order->buyer_id]);

                // Update dispute
                $dispute->update([
                    'status' => DisputeStatus::RESOLVED_RELEASE,
                    'resolved_by' => $adminId,
                    'resolution' => $resolutionText,
                    'resolved_at' => now(),
                ]);

                // Notify vendor
                $owner = $order->shop->owner;
                if ($owner) {
                    $msg = "Le litige concernant la commande {$order->reference} a été résolu en votre faveur. Les fonds ont été crédités.";
                    if ($owner->fcm_token) {
                        app(FirebasePushService::class)->sendPush($owner->fcm_token, "Litige Résolu", $msg);
                    }
                    app(SmsPushService::class)->sendSms($owner->phone, $msg);
                }

            } else {
                throw new \Exception('Action de résolution non supportée.');
            }

            return $dispute;
        });
    }
}
