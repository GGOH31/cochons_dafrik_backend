<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\RestaurantCommissionOverride;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Dispute;
use App\Models\Escrow;
use App\Models\Payment;
use App\Models\Order;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use App\Enums\UserRole;
use App\Enums\AccountStatus;
use App\Enums\DisputeStatus;
use App\Enums\EscrowStatus;
use App\Enums\PaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\WithdrawalStatus;
use App\Enums\TxType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminService
{
    public function validateShop(string $restaurantId, string $adminId, string $status): Restaurant
    {
        $restaurant = Restaurant::findOrFail($restaurantId);

        $restaurant->update([
            'status' => $status,
            'validated_by' => $adminId,
            'validated_at' => now(),
        ]);

        if ($restaurant->owner) {
            $restaurant->owner->update([
                'status' => $status,
            ]);
        }

        return $restaurant;
    }

    /**
     * Update commission rate override for a shop.
     */
    public function updateShopCommission(string $restaurantId, float $ratePct, string $adminId): RestaurantCommissionOverride
    {
        return RestaurantCommissionOverride::updateOrCreate(
            ['restaurant_id' => $restaurantId],
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
                    $dish = $item->dish;

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
                $owner = $order->restaurant->owner;
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

    /**
     * Create a vendeur account and its restaurant (+ wallet) together. Vendeurs never
     * self-register: an admin creates both the seller account and the shop in one go,
     * and it goes live immediately (no separate pending-review step, since the admin
     * already vetted it by creating it).
     */
    public function createRestaurantWithNewVendeur(array $vendeurData, array $shopData, string $adminId): Restaurant
    {
        return DB::transaction(function () use ($vendeurData, $shopData, $adminId) {
            $vendeur = User::create([
                'role' => UserRole::VENDEUR,
                'phone' => $vendeurData['phone'],
                'full_name' => $vendeurData['full_name'],
                'email' => $vendeurData['email'] ?? null,
                'password_hash' => Hash::make($vendeurData['password']),
                'status' => AccountStatus::ACTIVE,
                'phone_verified_at' => now(),
            ]);

            return app(\App\Services\VendeurService::class)->createShop($vendeur, $shopData, $adminId);
        });
    }

    /**
     * List restaurants with optional filters (status, search by name).
     */
    public function getRestaurants(array $filters): Builder
    {
        $query = Restaurant::with(['owner', 'wallet', 'commissionOverride'])->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'ILIKE', '%' . $filters['search'] . '%');
        }

        return $query;
    }

    /**
     * Update a restaurant's details (admin edit).
     */
    public function updateRestaurant(string $restaurantId, array $data): Restaurant
    {
        $restaurant = Restaurant::findOrFail($restaurantId);
        $restaurant->update($data);

        return $restaurant->fresh(['owner', 'wallet', 'commissionOverride']);
    }

    /**
     * List platform users (clients, vendeurs, grossistes) with optional filters.
     */
    public function getUsers(array $filters): Builder
    {
        $query = User::with('restaurant')->orderBy('created_at', 'desc');

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Activate/suspend/reject a user account.
     */
    public function updateUserStatus(string $userId, string $status): User
    {
        $user = User::findOrFail($userId);
        $user->update(['status' => $status]);

        return $user;
    }

    /**
     * List orders across the platform with optional filters.
     */
    public function getOrders(array $filters): Builder
    {
        $query = Order::with(['buyer', 'restaurant'])->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->whereIn('status', explode(',', $filters['status']));
        }

        if (!empty($filters['restaurant_id'])) {
            $query->where('restaurant_id', $filters['restaurant_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('reference', 'ILIKE', '%' . $filters['search'] . '%');
        }

        return $query;
    }

    /**
     * List withdrawal requests with optional status filter.
     */
    public function getWithdrawals(array $filters): Builder
    {
        $query = Withdrawal::with(['wallet.restaurant', 'processor'])->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * Mark a withdrawal request as done (paid out manually) or rejected
     * (refunding the amount back to the restaurant's wallet).
     */
    public function processWithdrawal(string $withdrawalId, string $adminId, string $action): Withdrawal
    {
        $withdrawal = Withdrawal::findOrFail($withdrawalId);

        if (!in_array($withdrawal->status, [WithdrawalStatus::PENDING, WithdrawalStatus::PROCESSING], true)) {
            throw new \Exception('Cette demande de retrait a déjà été traitée.');
        }

        if (!in_array($action, ['done', 'rejected'], true)) {
            throw new \Exception('Action non supportée.');
        }

        return DB::transaction(function () use ($withdrawal, $adminId, $action) {
            if ($action === 'rejected') {
                $wallet = $withdrawal->wallet;
                $wallet->increment('balance_fcfa', $withdrawal->amount_fcfa);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'tx_type' => TxType::REFUND,
                    'amount_fcfa' => $withdrawal->amount_fcfa,
                    'balance_after' => $wallet->fresh()->balance_fcfa,
                    'note' => "Retrait rejeté, montant recrédité au portefeuille.",
                ]);
            }

            $withdrawal->update([
                'status' => $action === 'done' ? WithdrawalStatus::DONE : WithdrawalStatus::REJECTED,
                'processed_by' => $adminId,
                'processed_at' => now(),
            ]);

            return $withdrawal;
        });
    }

    /**
     * List disputes, open ones by default.
     */
    public function getDisputes(array $filters): Builder
    {
        $query = Dispute::with(['order', 'opener'])->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * List all platform settings.
     */
    public function getPlatformSettings()
    {
        return PlatformSetting::orderBy('key')->get();
    }

    /**
     * Daily order report for a single restaurant, for the Excel export & yearly chart
     * on the admin dashboard: per-day counts of successful (completed), delivered,
     * cancelled/refused and disputed (complaint) orders, plus the total order value.
     */
    public function getRestaurantDailyReport(string $restaurantId, string $from, string $to): array
    {
        Restaurant::findOrFail($restaurantId);

        $rows = DB::table('orders')
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'completed') as success")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'delivered') as delivered")
            ->selectRaw("COUNT(*) FILTER (WHERE status IN ('cancelled', 'refused')) as cancelled")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'disputed') as complaints")
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("COALESCE(SUM(total_fcfa) FILTER (WHERE status != 'pending_payment'), 0) as total_fcfa")
            ->where('restaurant_id', $restaurantId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get();

        return $rows->map(fn ($row) => [
            'date' => $row->date,
            'success' => (int) $row->success,
            'delivered' => (int) $row->delivered,
            'cancelled' => (int) $row->cancelled,
            'complaints' => (int) $row->complaints,
            'total_orders' => (int) $row->total_orders,
            'total_fcfa' => (int) $row->total_fcfa,
        ])->all();
    }
}
