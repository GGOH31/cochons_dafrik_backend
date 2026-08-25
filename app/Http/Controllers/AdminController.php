<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRestaurantCommissionOverrideRequest;
use App\Http\Resources\RestaurantResource;
use App\Http\Resources\RestaurantCommissionOverrideResource;
use App\Http\Resources\PlatformSettingResource;
use App\Http\Resources\DisputeResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\WithdrawalResource;
use App\Services\AdminService;
use App\Enums\AccountStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Paginate a query builder and wrap it in the given API resource collection.
     */
    protected function paginatedResponse(Request $request, $query, string $resourceClass, string $message)
    {
        $perPage = (int) $request->get('per_page', 20);
        $paginated = $query->paginate($perPage);

        return $this->sendResponse([
            'data' => $resourceClass::collection($paginated->items()),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ], $message);
    }

    /**
     * Create a restaurant for a vendeur (shops are no longer self-created at registration).
     */
    public function createRestaurant(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => ['required', 'uuid', 'exists:users,id', 'unique:restaurants,owner_id'],
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string'],
            'logo_url' => ['nullable', 'string', 'url'],
            'commune' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_open' => ['nullable', 'boolean'],
            'delivery_fee_fcfa' => ['nullable', 'integer', 'min:0'],
            'min_order_fcfa' => ['nullable', 'integer', 'min:0'],
            'delivery_zone' => ['nullable', 'string'],
        ]);

        try {
            $restaurant = $this->adminService->createRestaurantForVendeur(
                $validated['owner_id'],
                $validated,
                $request->user()->id
            );

            return $this->sendResponse(new RestaurantResource($restaurant), 'Boutique créée avec succès.', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * List restaurants (filters: status, search).
     */
    public function getRestaurants(Request $request)
    {
        $query = $this->adminService->getRestaurants($request->only(['status', 'search']));

        return $this->paginatedResponse($request, $query, RestaurantResource::class, 'Boutiques récupérées.');
    }

    /**
     * Get a single restaurant's detail.
     */
    public function getRestaurant(Request $request, $id)
    {
        try {
            $restaurant = \App\Models\Restaurant::with(['owner', 'wallet', 'commissionOverride'])->findOrFail($id);

            return $this->sendResponse(new RestaurantResource($restaurant), 'Boutique récupérée.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * List platform users (filters: role, status, search).
     */
    public function getUsers(Request $request)
    {
        $query = $this->adminService->getUsers($request->only(['role', 'status', 'search']));

        return $this->paginatedResponse($request, $query, UserResource::class, 'Utilisateurs récupérés.');
    }

    /**
     * Get a single user's detail.
     */
    public function getUser(Request $request, $id)
    {
        try {
            $user = \App\Models\User::with(['restaurant', 'addresses'])->findOrFail($id);

            return $this->sendResponse(new UserResource($user), 'Utilisateur récupéré.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Activate / suspend / reject a user account.
     */
    public function updateUserStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', new Enum(AccountStatus::class)],
        ]);

        try {
            $user = $this->adminService->updateUserStatus($id, $request->status);

            return $this->sendResponse(new UserResource($user), 'Statut de l\'utilisateur mis à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * List orders across the platform (filters: status, restaurant_id, search).
     */
    public function getOrders(Request $request)
    {
        $query = $this->adminService->getOrders($request->only(['status', 'restaurant_id', 'search']));

        return $this->paginatedResponse($request, $query, OrderResource::class, 'Commandes récupérées.');
    }

    /**
     * Get a single order's detail.
     */
    public function getOrder(Request $request, $id)
    {
        try {
            $order = \App\Models\Order::with(['buyer', 'restaurant', 'items.dish', 'payments', 'escrow', 'dispute'])
                ->findOrFail($id);

            return $this->sendResponse(new OrderResource($order), 'Commande récupérée.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * List withdrawal requests (filter: status).
     */
    public function getWithdrawals(Request $request)
    {
        $query = $this->adminService->getWithdrawals($request->only(['status']));

        return $this->paginatedResponse($request, $query, WithdrawalResource::class, 'Retraits récupérés.');
    }

    /**
     * Mark a withdrawal as done (paid out) or rejected.
     */
    public function processWithdrawal(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:done,rejected'],
        ]);

        try {
            $withdrawal = $this->adminService->processWithdrawal($id, $request->user()->id, $validated['action']);

            return $this->sendResponse(new WithdrawalResource($withdrawal), 'Retrait mis à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * List disputes (filter: status, defaults to open).
     */
    public function getDisputes(Request $request)
    {
        $query = $this->adminService->getDisputes($request->only(['status']));

        return $this->paginatedResponse($request, $query, DisputeResource::class, 'Litiges récupérés.');
    }

    /**
     * List all platform settings.
     */
    public function getPlatformSettings(Request $request)
    {
        try {
            $settings = $this->adminService->getPlatformSettings();

            return $this->sendResponse(PlatformSettingResource::collection($settings), 'Réglages de la plateforme récupérés.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Daily order report for a restaurant (counts by outcome + total value),
     * used for the admin stats page's chart and Excel export.
     */
    public function getRestaurantReport(Request $request, $restaurantId)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $to = $validated['to'] ?? now()->toDateString();
        $from = $validated['from'] ?? now()->subYear()->addDay()->toDateString();

        try {
            $report = $this->adminService->getRestaurantDailyReport($restaurantId, $from, $to);

            return $this->sendResponse([
                'restaurant_id' => $restaurantId,
                'from' => $from,
                'to' => $to,
                'days' => $report,
            ], 'Rapport du restaurant récupéré.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Validate a shop (approve / reject).
     */
    public function validateShop(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', new Enum(AccountStatus::class)],
        ]);

        try {
            $restaurant = $this->adminService->validateShop($id, $request->user()->id, $request->status);
            return $this->sendResponse(new RestaurantResource($restaurant), 'Le statut de la boutique a été mis à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Manage commissions override for a specific shop.
     */
    public function updateShopCommission(UpdateRestaurantCommissionOverrideRequest $request, $restaurantId)
    {
        try {
            $override = $this->adminService->updateShopCommission($restaurantId, $request->rate_pct, $request->user()->id);
            return $this->sendResponse(new RestaurantCommissionOverrideResource($override), 'Commission spécifique de la boutique mise à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Manage global platform commission settings.
     */
    public function updatePlatformSetting(Request $request, $key)
    {
        $request->validate([
            'value' => ['required'],
        ]);

        try {
            $setting = $this->adminService->updatePlatformSetting($key, $request->value, $request->user()->id);
            return $this->sendResponse(new PlatformSettingResource($setting), 'Paramètre de la plateforme mis à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Get platform statistics.
     */
    public function getDashboardStats(Request $request)
    {
        try {
            $stats = $this->adminService->getDashboardStats();
            return $this->sendResponse($stats, 'Statistiques de la plateforme récupérées.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Supervise active escrows.
     */
    public function getEscrows(Request $request)
    {
        try {
            $escrows = $this->adminService->getEscrows();
            return $this->sendResponse($escrows, 'Séquestres en cours récupérés.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Resolve an open dispute.
     */
    public function resolveDispute(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:refund,release'],
            'resolution_text' => ['required', 'string'],
        ]);

        try {
            $dispute = $this->adminService->resolveDispute($id, $request->user()->id, $validated['action'], $validated['resolution_text']);
            return $this->sendResponse(new DisputeResource($dispute), 'Le litige a été résolu.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }
}
