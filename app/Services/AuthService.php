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
     * Send a 6-digit verification code via OTP SMS using IkoddiService.
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

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (!str_starts_with($cleanPhone, '225')) {
            $cleanPhone = '225' . $cleanPhone;
        }
        $recipient = $cleanPhone;

        // Send OTP via SMS using IkoddiService
        $message = "Votre code de validation Cochons d'Afrik est : {$code}. Valable 5 minutes.";
        app(IkoddiService::class)->sendSms([$recipient], $message, 'Ikoddi', 'CI', '225');

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
            if (!$user->phone_verified_at) {
                $user->phone_verified_at = now();
            }

            // Client status flow: PENDING -> ACTIVE
            if ($user->role === UserRole::CLIENT && $user->status === AccountStatus::PENDING) {
                $user->status = AccountStatus::ACTIVE;
            }
            // Vendeur status flow: PENDING -> SUSPENDED
            elseif ($user->role === UserRole::VENDEUR && $user->status === AccountStatus::PENDING) {
                $user->status = AccountStatus::SUSPENDED;
            }

            $user->save();

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
        if (User::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Ce numéro de téléphone est déjà utilisé.'],
            ]);
        }

        $user = User::create([
            'role' => UserRole::CLIENT,
            'phone' => $data['phone'],
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'password_hash' => isset($data['password']) ? Hash::make($data['password']) : null,
            'fcm_token' => $data['fcm_token'] ?? null,
            'status' => AccountStatus::PENDING,
            'phone_verified_at' => null,
        ]);

        return [
            'user' => $user,
        ];
    }

    /**
     * Register a new vendeur (seller).
     */
    public function registerVendeur(array $data): array
    {
        if (User::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Ce numéro de téléphone est déjà utilisé.'],
            ]);
        }

        $user = User::create([
            'role' => UserRole::VENDEUR,
            'phone' => $data['phone'],
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'password_hash' => isset($data['password']) ? Hash::make($data['password']) : null,
            'fcm_token' => $data['fcm_token'] ?? null,
            'status' => AccountStatus::PENDING,
            'phone_verified_at' => null,
        ]);

        return [
            'user' => $user,
        ];
    }

    /**
     * Authenticate user and return token.
     */
    public function login(array $data): array
    {
        $user = User::where('phone', $data['phone'])->first();


        if($user == null){
            throw ValidationException::withMessages([
                'phone' => ['L\'utilisateur n\'existe pas.'],
            ]);
        }

        if (!Hash::check($data['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'phone' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }


        if ($user->status === AccountStatus::PENDING) {
            throw ValidationException::withMessages([
                'phone' => ['Veuillez d\'abord valider votre numéro de téléphone.'],
            ]);
        }

        if ($user->status === AccountStatus::SUSPENDED) {
            $msg = $user->role === UserRole::VENDEUR
                ? "Votre compte est en attente de validation par l'administration."
                : "Votre compte a été suspendu.";
            throw ValidationException::withMessages([
                'phone' => [$msg],
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

        if ($user->role === UserRole::VENDEUR) {
            $user->load('restaurant');
        }

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Send a reset password OTP code to the user's phone if they exist.
     */
    public function sendForgotPasswordOtp(string $phone): string
    {
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => ["Aucun utilisateur n'est associé à ce numéro de téléphone."],
            ]);
        }

        $code = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        // Save hashed OTP code
        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (!str_starts_with($cleanPhone, '225')) {
            $cleanPhone = '225' . $cleanPhone;
        }
        $recipient = $cleanPhone;

        // Send OTP via SMS using IkoddiService
        $message = "Votre code de réinitialisation de mot de passe Cochons d'Afrik est : {$code}. Valable 5 minutes.";
        app(IkoddiService::class)->sendSms([$recipient], $message, 'Ikoddi', 'CI', '225');

        return $code;
    }

    /**
     * Reset the user's password using the OTP code.
     */
    public function resetPasswordWithOtp(string $phone, string $code, string $password): void
    {
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => ["L'utilisateur n'existe pas."],
            ]);
        }

        $otp = OtpCode::where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'desc')
            ->first();

        if (!$otp || !Hash::check($code, $otp->code_hash)) {
            throw ValidationException::withMessages([
                'code' => ['Le code de réinitialisation OTP est incorrect ou a expiré.'],
            ]);
        }

        // Mark OTP as used
        $otp->update(['used_at' => now()]);

        // Update user's password
        $user->password_hash = Hash::make($password);
        $user->save();
    }
}
