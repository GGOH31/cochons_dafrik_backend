<?php

namespace App\Services;

use App\Models\User;
use App\Models\OtpCode;
use App\Enums\UserRole;
use App\Enums\AccountStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Send a 6-digit verification code via OTP SMS.
     */
    public function sendOtp(string $phone): string
    {
        $code = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        // Save hashed OTP code
        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
        ]);

        // Send OTP via SMS
        $message = "Votre code de validation Cochons d'Afrik est : {$code}. Valable 5 minutes.";
        app(SmsPushService::class)->sendSms($phone, $message);

        // For simulation/debug purposes we return the code (in prod you might return status)
        return $code;
    }

    /**
     * Verify the SMS OTP code.
     */
    public function verifyOtp(string $phone, string $code): array
    {
        $otp = OtpCode::where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'desc')
            ->first();

        if (!$otp || !Hash::check($code, $otp->code_hash)) {
            throw ValidationException::withMessages([
                'code' => ['Le code de validation OTP est incorrect ou a expiré.'],
            ]);
        }

        // Mark OTP as used
        $otp->update(['used_at' => now()]);

        // Check if user exists
        $user = User::where('phone', $phone)->first();

        if ($user) {
            // Verify phone if not already done
            if (!$user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }

            if ($user->status === AccountStatus::SUSPENDED) {
                throw ValidationException::withMessages([
                    'phone' => ['Votre compte a été suspendu.'],
                ]);
            }

            if ($user->status === AccountStatus::REJECTED) {
                throw ValidationException::withMessages([
                    'phone' => ['Votre inscription a été rejetée.'],
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'registered' => true,
                'user' => $user,
                'token' => $token,
            ];
        }

        return [
            'registered' => false,
            'phone' => $phone,
        ];
    }

    /**
     * Register a new client.
     */
    public function registerClient(array $data): array
    {
        $user = User::create([
            'role' => UserRole::CLIENT,
            'phone' => $data['phone'],
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'password_hash' => isset($data['password']) ? Hash::make($data['password']) : null,
            'fcm_token' => $data['fcm_token'] ?? null,
            'status' => AccountStatus::ACTIVE,
            'phone_verified_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Register a new vendeur (seller).
     */
    public function registerVendeur(array $data): array
    {
        $user = User::create([
            'role' => UserRole::VENDEUR,
            'phone' => $data['phone'],
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'password_hash' => isset($data['password']) ? Hash::make($data['password']) : null,
            'fcm_token' => $data['fcm_token'] ?? null,
            'status' => AccountStatus::PENDING, // Vendeurs are pending until admin approval
            'phone_verified_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Authenticate user and return token.
     */
    public function login(array $data): array
    {
        $user = User::where('phone', $data['phone'])->first();

        if (!$user || !Hash::check($data['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'phone' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if ($user->status === AccountStatus::SUSPENDED) {
            throw ValidationException::withMessages([
                'phone' => ['Votre compte a été suspendu.'],
            ]);
        }

        if ($user->status === AccountStatus::REJECTED) {
            throw ValidationException::withMessages([
                'phone' => ['Votre inscription a été rejetée.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        if (isset($data['fcm_token'])) {
            $user->update(['fcm_token' => $data['fcm_token']]);
        }

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
