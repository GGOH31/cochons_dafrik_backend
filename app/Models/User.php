<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'role',
        'phone',
        'phone_verified_at',
        'full_name',
        'email',
        'password_hash',
        'fcm_token',
        'status',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Get the password for the user.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'status' => AccountStatus::class,
            'phone_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relationships
    public function restaurant()
    {
        return $this->hasOne(Restaurant::class, 'owner_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'author_id');
    }

    public function disputes()
    {
        return $this->hasMany(Dispute::class, 'opened_by');
    }

    public function resolvedDisputes()
    {
        return $this->hasMany(Dispute::class, 'resolved_by');
    }

    public function validatedRestaurants()
    {
        return $this->hasMany(Restaurant::class, 'validated_by');
    }

    public function processedWithdrawals()
    {
        return $this->hasMany(Withdrawal::class, 'processed_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
