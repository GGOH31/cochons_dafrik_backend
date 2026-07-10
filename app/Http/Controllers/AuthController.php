<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Enums\UserRole;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Send OTP SMS to the phone number.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:10'],
        ]);

        try {
            $code = $this->authService->sendOtp($request->phone);
            return $this->sendResponse([
                'phone' => $request->phone,
                'debug_code' => $code, // Returned for sandbox testing ease
            ], 'Code OTP envoyé avec succès.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Verify SMS OTP code.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:10'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $result = $this->authService->verifyOtp($request->phone, $request->code);

            if ($result['registered']) {
                return $this->sendResponse([
                    'registered' => true,
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                ], 'Validation OTP réussie, vous êtes connecté.');
            }

            return $this->sendResponse([
                'registered' => false,
                'phone' => $result['phone'],
            ], 'Validation OTP réussie. Veuillez compléter votre inscription.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    public function register(StoreUserRequest $request)
    {
        $data = $request->validated();

        if ($data['role'] === UserRole::VENDEUR->value) {
            $result = $this->authService->registerVendeur($data);
            
            if (!empty($data['shop'])) {
                app(\App\Services\VendeurService::class)->createShop($result['user'], $data['shop']);
            }
            
        } else {
            $result = $this->authService->registerClient($data);
        }

        $message = 'Inscription réussie , un code de confirmation de 6 chiffres vous sera envoyé par SMS.';


        return $this->sendResponse([
            'user' => new UserResource($result['user']),
        ], $message, 201);
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'fcm_token' => ['nullable', 'string'],
        ]);

        try {
            $result = $this->authService->login($credentials);

            return $this->sendResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Connexion réussie.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse(null, 'Déconnexion réussie.');
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request)
    {
        return $this->sendResponse(new UserResource($request->user()), 'Utilisateur connecté récupéré.');
    }
}
