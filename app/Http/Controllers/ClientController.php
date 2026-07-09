<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\ShopResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ReviewResource;
use App\Services\ClientService;
use App\Enums\OrderType;
use App\Enums\DeliveryMode;
use App\Enums\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Save address.
     */
    public function saveAddress(StoreAddressRequest $request)
    {
        try {
            $address = $this->clientService->saveAddress($request->user(), $request->validated());
            return $this->sendResponse(new AddressResource($address), 'Adresse de livraison enregistrée.', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Search shops.
     */
    public function searchShops(Request $request)
    {
        $filters = $request->validate([
            'name' => ['nullable', 'string'],
            'commune' => ['nullable', 'string'],
            'is_open' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'max_distance' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $shops = $this->clientService->searchShops($filters);
            return $this->sendResponse(ShopResource::collection($shops), 'Boutiques récupérées avec succès.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Get products of a specific shop.
     */
    public function getShopProducts(Request $request, $shopId)
    {
        $filters = $request->validate([
            'name' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer'],
            'price_min' => ['nullable', 'integer', 'min:0'],
            'price_max' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $products = $this->clientService->getShopProducts($shopId, $filters);
            return $this->sendResponse(ProductResource::collection($products), 'Produits de la boutique récupérés.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Create a new order (cart checkout).
     */
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'order_type' => ['required', new Enum(OrderType::class)],
            'delivery_mode' => ['required', new Enum(DeliveryMode::class)],
            'address_id' => ['required_if:delivery_mode,' . DeliveryMode::DELIVERY->value, 'nullable', 'uuid', 'exists:addresses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.options' => ['nullable', 'array'],
        ]);

        try {
            $order = $this->clientService->createOrder($request->user(), $validated);
            return $this->sendResponse(new OrderResource($order), 'Commande créée avec succès, en attente de paiement.', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Simulate online payment.
     */
    public function payOrder(Request $request, $id)
    {
        $validated = $request->validate([
            'provider' => ['required', new Enum(PaymentProvider::class)],
            'provider_ref' => ['required', 'string', 'max:120'],
        ]);

        try {
            $order = $this->clientService->payOrder($id, $validated['provider'], $validated['provider_ref']);
            return $this->sendResponse(new OrderResource($order), 'Paiement effectué et montant séquestré.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Confirm order reception (releasing escrow funds).
     */
    public function confirmReception(Request $request, $id)
    {
        try {
            $order = $this->clientService->confirmReception($id, $request->user());
            return $this->sendResponse(new OrderResource($order), 'Réception confirmée et paiement libéré.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Submit review.
     */
    public function submitReview(Request $request, $id)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string'],
        ]);

        try {
            $review = $this->clientService->submitReview($id, $request->user(), $validated);
            return $this->sendResponse(new ReviewResource($review), 'Avis enregistré avec succès.', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Re-order from a past order.
     */
    public function reorder(Request $request, $id)
    {
        try {
            $order = $this->clientService->reorder($id, $request->user());
            return $this->sendResponse(new OrderResource($order), 'Nouvelle commande recréée avec succès.', 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Retrieve all orders for the current client.
     */
    public function getMyOrders(Request $request)
    {
        $orders = \App\Models\Order::where('buyer_id', $request->user()->id)
            ->with(['items', 'shop'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->sendResponse(OrderResource::collection($orders)->response()->getData(true), 'Commandes récupérées.');
    }
}
