<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'role' => \App\Enums\UserRole::CLIENT,
            'phone' => fake()->unique()->numerify('##########'),
            'phone_verified_at' => now(),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => Hash::make('password'),
            'fcm_token' => null,
            'status' => \App\Enums\AccountStatus::ACTIVE,
        ];
    }
}
