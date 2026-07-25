<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRestaurantRequest;


use App\Http\Requests\StoreDishRequest;
use App\Http\Requests\UpdateDishRequest;
use App\Http\Resources\RestaurantResource;
use App\Http\Resources\UserResource;

use App\Http\Resources\DishResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\WalletResource;
use App\Http\Resources\PromotionResource;
use App\Services\VendeurService;
use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class VendeurController extends Controller
{
    protected $vendeurService;

    public function __construct(VendeurService $vendeurService)
    {
        $this->vendeurService = $vendeurService;
    }

    /**
     * Create shop & wallet.
     */
    public function createShop(StoreRestaurantRequest $request)
    {
        try {
            $restaurant = $this->vendeurService->createShop($request->user(), $request->validated());
            return $this->sendResponse(new RestaurantResource($restaurant), 'Boutique et portefeuille créés avec succès.', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Update vendor personal profile and shop details.
     */
    public function updateProfile(Request $request)
    {
        $userRules = [
            'full_name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:160', 'unique:users,email,' . $request->user()->id],
            'password' => ['nullable', 'string', 'min:6'],
            'fcm_token' => ['nullable', 'string'],
        ];

        $restaurantRules = [
            'name' => ['sometimes', 'string', 'max:140'],
            'description' => ['nullable', 'string'],
            'logo_url' => ['nullable'],
            'commune' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_open' => ['sometimes', 'boolean'],
            'delivery_fee_fcfa' => ['sometimes', 'integer', 'min:0'],
            'min_order_fcfa' => ['sometimes', 'integer', 'min:0'],
        ];

        $validatedUser = $request->validate($userRules);
        $validatedShop = $request->has('restaurant') ? $request->validate(['restaurant' => ['array']])['restaurant'] : [];
        if (!empty($validatedShop)) {
            $validator = validator($validatedShop, $restaurantRules);
            if ($validator->fails()) {
                return $this->sendError('Données de boutique invalides.', $validator->errors()->toArray(), 422);
            }
            $validatedShop = $validator->validated();
        }

        if ($request->hasFile('shop.logo_url')) {
            $uploaded = \App\Services\CloudinaryService::uploadImage($request->file('shop.logo_url'), 'restaurants');
            $validatedShop['logo_url'] = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
        } elseif ($request->hasFile('logo_url')) {
            $uploaded = \App\Services\CloudinaryService::uploadImage($request->file('logo_url'), 'restaurants');
            $validatedShop['logo_url'] = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
        }

        try {
            $user = $this->vendeurService->updateProfile($request->user(), $validatedUser, $validatedShop);
            return $this->sendResponse(new UserResource($user), 'Profil et boutique mis à jour avec succès.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Get vendor's personal profile information.
     */
    public function getPersonalInfo(Request $request)
    {
        try {
            $user = $request->user();
            return $this->sendResponse(new UserResource($user), 'Informations personnelles récupérées avec succès.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Get vendor's shop details.
     */
    public function getShopInfo(Request $request)
    {
        try {
            $restaurant = $this->getAuthShop($request);
            if (!$restaurant) {
                return $this->sendError('Aucune boutique trouvée pour ce vendeur.', [], 404);
            }
            return $this->sendResponse(new RestaurantResource($restaurant), 'Informations de la boutique récupérées avec succès.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Update vendor's shop details.
     */
    public function updateShopInfo(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Aucune boutique trouvée pour ce vendeur.', [], 404);
        }

        $rules = [
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_open' => ['sometimes', 'boolean'],
            'delivery_fee_fcfa' => ['sometimes', 'integer', 'min:0'],
            'min_order_fcfa' => ['sometimes', 'integer', 'min:0'],
            'opening_hours' => ['nullable', 'array'],
            'delivery_zone' => ['nullable', 'string'],
        ];

        $validated = $request->validate($rules);

        if ($request->hasFile('logo_url')) {
            $uploaded = \App\Services\CloudinaryService::uploadImage($request->file('logo_url'), 'restaurants');
            $validated['logo_url'] = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
        } elseif ($request->hasFile('logo_file')) {
            $uploaded = \App\Services\CloudinaryService::uploadImage($request->file('logo_file'), 'restaurants');
            $validated['logo_url'] = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
        }

        try {
            $restaurant->update($validated);
            return $this->sendResponse(new RestaurantResource($restaurant->fresh()), 'Boutique mise à jour avec succès.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    

    /**
     * Get dishes of the authenticated vendor's shop.
     */
    public function getDishes(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $dishes = \App\Models\Dish::where('restaurant_id', $restaurant->id)
            ->with(['restaurant', 'promotions', 'accompaniments'])
            ->get();

        return $this->sendResponse(\App\Http\Resources\DishResource::collection($dishes), 'Produits récupérés.');
    }

    /**
     * Create a product for the vendor's shop.
     */
    public function createProduct(StoreDishRequest $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Vous devez d\'abord créer une boutique.', [], 403);
        }

        $validated = $request->validated();
        if ($validated['restaurant_id'] !== $restaurant->id) {
            return $this->sendError('ID de boutique non correspondant.', [], 403);
        }

        try {
            $dish = $this->vendeurService->createProduct($validated);
            return $this->sendResponse(new DishResource($dish), 'Produit créé avec succès.', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Update product.
     */
    public function updateProduct(UpdateDishRequest $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $validated = $request->validated();
        if ($request->hasFile('photo_url')) {
            $uploaded = \App\Services\CloudinaryService::uploadImage($request->file('photo_url'), 'dishes');
            $validated['photo_url'] = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;
        }

        try {
            $dish = $this->vendeurService->updateProduct($id, $validated, $restaurant->id);
            return $this->sendResponse(new DishResource($dish), 'Produit mis à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Delete product.
     */
    public function deleteProduct(Request $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        try {
            $this->vendeurService->deleteProduct($id, $restaurant->id);
            return $this->sendResponse(null, 'Produit supprimé.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Get accompaniments of the authenticated vendor's shop.
     */
    public function getAccompaniments(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $accompaniments = $this->vendeurService->getAccompaniments($restaurant->id);
        return $this->sendResponse($accompaniments, 'Accompagnements récupérés.');
    }

    /**
     * Create an accompaniment.
     */
    public function createAccompaniment(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $validated = $request->validate([
            'dish_id' => ['required', 'uuid', 'exists:dishes,id'],
            'name' => ['required', 'string', 'max:120'],
            'prix_unit' => ['required', 'integer', 'min:0'],
            'photo_file' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $dish = \App\Models\Dish::where('id', $validated['dish_id'])->where('restaurant_id', $restaurant->id)->first();
        if (!$dish) {
            return $this->sendError('Produit non autorisé.', [], 403);
        }

        try {
            $accompaniment = $this->vendeurService->createAccompaniment($validated);
            return $this->sendResponse($accompaniment, 'Accompagnement créé.', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Update an accompaniment.
     */
    public function updateAccompaniment(Request $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $validated = $request->validate([
            'dish_id' => ['nullable', 'uuid', 'exists:dishes,id'],
            'name' => ['nullable', 'string', 'max:120'],
            'prix_unit' => ['nullable', 'integer', 'min:0'],
            'photo_url' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        if (isset($validated['photo_url'])) {
            $validated['photo_file'] = $validated['photo_url'];
            unset($validated['photo_url']);
        }

        $accompaniment = \App\Models\Accompaniment::findOrFail($id);
        $dish = \App\Models\Dish::where('id', $accompaniment->dish_id)->where('restaurant_id', $restaurant->id)->first();
        if (!$dish) {
            return $this->sendError('Accompagnement non autorisé.', [], 403);
        }

        if (!empty($validated['dish_id'])) {
            $newProduct = \App\Models\Dish::where('id', $validated['dish_id'])->where('restaurant_id', $restaurant->id)->first();
            if (!$newProduct) {
                return $this->sendError('Nouveau produit non autorisé.', [], 403);
            }
        }

        try {
            $accompaniment = $this->vendeurService->updateAccompaniment($id, $validated);
            return $this->sendResponse($accompaniment, 'Accompagnement mis à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Delete an accompaniment.
     */
    public function deleteAccompaniment(Request $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $accompaniment = \App\Models\Accompaniment::findOrFail($id);
        $dish = \App\Models\Dish::where('id', $accompaniment->dish_id)->where('restaurant_id', $restaurant->id)->first();
        if (!$dish) {
            return $this->sendError('Accompagnement non autorisé.', [], 403);
        }

        try {
            $this->vendeurService->deleteAccompaniment($id);
            return $this->sendResponse(null, 'Accompagnement supprimé.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Get promotions of the authenticated vendor's shop.
     */
    public function getPromotions(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $promotions = $this->vendeurService->getPromotions($restaurant->id);
        return $this->sendResponse(PromotionResource::collection($promotions), 'Promotions récupérées.');
    }

    /**
     * Create a promotion.
     */
    public function createPromotion(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $validated = $request->validate([
            'dish_id' => ['required', 'uuid', 'exists:dishes,id'],
            'title' => ['required', 'string', 'max:140'],
            'promo_type' => ['required', 'string', 'in:percentage,fixed_price'],
            'value' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['restaurant_id'] = $restaurant->id;
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        $dish = \App\Models\Dish::where('id', $validated['dish_id'])->where('restaurant_id', $restaurant->id)->first();
        if (!$dish) {
            return $this->sendError('Produit non autorisé.', [], 403);
        }

        try {
            $promotion = $this->vendeurService->createPromotion($validated);
            return $this->sendResponse(new PromotionResource($promotion), 'Promotion créée.', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Update a promotion.
     */
    public function updatePromotion(Request $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $validated = $request->validate([
            'dish_id' => ['nullable', 'uuid', 'exists:dishes,id'],
            'title' => ['nullable', 'string', 'max:140'],
            'promo_type' => ['nullable', 'string', 'in:percentage,fixed_price'],
            'value' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $promotion = \App\Models\Promotion::findOrFail($id);
        if ($promotion->restaurant_id !== $restaurant->id) {
            return $this->sendError('Promotion non autorisée.', [], 403);
        }

        if (!empty($validated['dish_id'])) {
            $dish = \App\Models\Dish::where('id', $validated['dish_id'])->where('restaurant_id', $restaurant->id)->first();
            if (!$dish) {
                return $this->sendError('Produit non autorisé.', [], 403);
            }
        }

        try {
            $promotion = $this->vendeurService->updatePromotion($id, $validated);
            return $this->sendResponse(new PromotionResource($promotion), 'Promotion mise à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Delete a promotion.
     */
    public function deletePromotion(Request $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $promotion = \App\Models\Promotion::findOrFail($id);
        if ($promotion->restaurant_id !== $restaurant->id) {
            return $this->sendError('Promotion non autorisée.', [], 403);
        }

        try {
            $this->vendeurService->deletePromotion($id);
            return $this->sendResponse(null, 'Promotion supprimée.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Accept order.
     */
    public function acceptOrder(Request $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        try {
            $order = $this->vendeurService->acceptOrder($id, $restaurant->id);
            return $this->sendResponse(new OrderResource($order), 'Commande acceptée.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Refuse order.
     */
    public function refuseOrder(Request $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        try {
            $order = $this->vendeurService->refuseOrder($id, $restaurant->id);
            return $this->sendResponse(new OrderResource($order), 'Commande refusée et client remboursé.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $request->validate([
            'status' => ['required', new Enum(OrderStatus::class)],
        ]);

        try {
            $order = $this->vendeurService->updateOrderStatus($id, $request->status, $restaurant->id);
            return $this->sendResponse(new OrderResource($order), 'Statut de la commande mis à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Retrieve wallet details and balance.
     */
    public function getWallet(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $wallet = \App\Models\Wallet::where('restaurant_id', $restaurant->id)
            ->with(['transactions' => fn($q) => $q->orderBy('created_at', 'desc')])
            ->firstOrFail();

        return $this->sendResponse(new WalletResource($wallet), 'Portefeuille récupéré.');
    }

    /**
     * Request withdrawal to Mobile Money.
     */
    public function requestWithdrawal(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $validated = $request->validate([
            'amount_fcfa' => ['required', 'integer', 'min:1'],
            'provider' => ['required', new Enum(PaymentProvider::class)],
            'dest_phone' => ['required', 'string', 'max:20'],
        ]);

        try {
            $withdrawal = $this->vendeurService->requestWithdrawal($restaurant->id, $validated['amount_fcfa'], $validated['provider'], $validated['dest_phone']);
            return $this->sendResponse($withdrawal, 'Demande de retrait soumise avec succès.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Retrieve vendor orders.
     */
    public function getMyOrders(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        $query = \App\Models\Order::where('restaurant_id', $restaurant->id)->with(['items.dish', 'buyer', 'address']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('per_page')) {
            $orders = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page'));
            return $this->sendResponse(OrderResource::collection($orders)->response()->getData(true), 'Commandes récupérées.');
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return $this->sendResponse(OrderResource::collection($orders), 'Commandes récupérées.');
    }

    /**
     * Get details of a specific order for the vendor shop.
     */
    public function getOrderDetails(Request $request, $id)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        try {
            $order = \App\Models\Order::where('id', $id)
                ->where('restaurant_id', $restaurant->id)
                ->with(['items.dish', 'buyer', 'address', 'payments', 'escrow', 'review'])
                ->firstOrFail();

            return $this->sendResponse(new OrderResource($order), 'Détails de la commande récupérés.');
        } catch (\Exception $e) {
            return $this->sendError('Commande introuvable.', [], 404);
        }
    }

    /**
     * Get vendor dashboard statistics and latest orders.
     */
    public function getDashboard(Request $request)
    {
        $restaurant = $this->getAuthShop($request);
        if (!$restaurant) {
            return $this->sendError('Boutique introuvable.', [], 403);
        }

        try {
            $user = $request->user();
            $wallet = \App\Models\Wallet::where('restaurant_id', $restaurant->id)->first();
            $balanceFcfa = $wallet ? (int) $wallet->balance_fcfa : 0;

            $escrowFcfa = (int) \App\Models\Escrow::whereHas('order', fn($q) => $q->where('restaurant_id', $restaurant->id))
                ->where('status', \App\Enums\EscrowStatus::HELD)
                ->sum('amount_fcfa');

            $todayOrdersCount = \App\Models\Order::where('restaurant_id', $restaurant->id)
                ->whereDate('created_at', now()->today())
                ->count();

            $totalOrdersCount = \App\Models\Order::where('restaurant_id', $restaurant->id)->count();

            $override = $restaurant->commissionOverride;
            $commissionPct = $override ? (float) $override->commission_pct : 3.0;

            $latestOrder = \App\Models\Order::where('restaurant_id', $restaurant->id)
                ->whereIn('status', [\App\Enums\OrderStatus::PAID->value, \App\Enums\OrderStatus::PENDING_PAYMENT->value])
                ->with(['items.dish', 'buyer', 'address'])
                ->latest()
                ->first();

            if (!$latestOrder) {
                $latestOrder = \App\Models\Order::where('restaurant_id', $restaurant->id)
                    ->with(['items.dish', 'buyer', 'address'])
                    ->latest()
                    ->first();
            }

            $dashboardData = [
                'restaurant' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'commune' => $restaurant->commune,
                    'address' => $restaurant->address,
                    'is_open' => (bool) $restaurant->is_open,
                    'rating_avg' => (float) ($restaurant->rating_avg ?? 5.0),
                    'rating_count' => (int) ($restaurant->rating_count ?? 0),
                ],
                'vendor' => [
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                ],
                'wallet' => [
                    'balance_fcfa' => $balanceFcfa,
                    'escrow_fcfa' => $escrowFcfa,
                ],
                'stats' => [
                    'today_orders_count' => $todayOrdersCount,
                    'total_orders_count' => $totalOrdersCount,
                    'rating_avg' => (float) ($restaurant->rating_avg ?? 5.0),
                    'commission_pct' => $commissionPct,
                ],
                'latest_order' => $latestOrder ? new OrderResource($latestOrder) : null,
            ];

            return $this->sendResponse($dashboardData, 'Tableau de bord récupéré avec succès.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }
}
