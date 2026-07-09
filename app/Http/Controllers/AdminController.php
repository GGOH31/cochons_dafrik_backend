<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateShopCommissionOverrideRequest;
use App\Http\Resources\ShopResource;
use App\Http\Resources\ShopCommissionOverrideResource;
use App\Http\Resources\PlatformSettingResource;
use App\Http\Resources\DisputeResource;
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
     * Validate a shop (approve / reject).
     */
    public function validateShop(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', new Enum(AccountStatus::class)],
        ]);

        try {
            $shop = $this->adminService->validateShop($id, $request->user()->id, $request->status);
            return $this->sendResponse(new ShopResource($shop), 'Le statut de la boutique a été mis à jour.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Manage commissions override for a specific shop.
     */
    public function updateShopCommission(UpdateShopCommissionOverrideRequest $request, $shopId)
    {
        try {
            $override = $this->adminService->updateShopCommission($shopId, $request->rate_pct, $request->user()->id);
            return $this->sendResponse(new ShopCommissionOverrideResource($override), 'Commission spécifique de la boutique mise à jour.');
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
