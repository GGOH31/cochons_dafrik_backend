<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\AccountStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '+2250102030405'],
            [
                'role' => UserRole::ADMIN,
                'full_name' => 'Cochons d\'Afrik Admin',
                'email' => 'admin@cochonsdafrik.com',
                'password_hash' => Hash::make('Admin2026!'),
                'status' => AccountStatus::ACTIVE,
                'phone_verified_at' => now(),
            ]
        );
    }
}
