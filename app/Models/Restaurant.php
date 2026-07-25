<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'logo_url',
        'commune',
        'address',
        'latitude',
        'longitude',
        'is_open',
        'delivery_fee_fcfa',
        'min_order_fcfa',
        'status',
        'validated_by',
        'validated_at',
        'rating_avg',
        'rating_count',
        'supporting_docs_url',
        'opening_hours',
        'delivery_zone',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountStatus::class,
            'is_open' => 'boolean',
            'delivery_fee_fcfa' => 'integer',
            'min_order_fcfa' => 'integer',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'validated_at' => 'datetime',
            'rating_avg' => 'decimal:1',
            'rating_count' => 'integer',
            'opening_hours' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function dishes()
    {
        return $this->hasMany(Dish::class);
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function commissionOverride()
    {
        return $this->hasOne(RestaurantCommissionOverride::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
