<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reference',
        'order_type',
        'buyer_id',
        'restaurant_id',
        'status',
        'delivery_mode',
        'address_id',
        'delivery_code',
        'subtotal_fcfa',
        'delivery_fcfa',
        'total_fcfa',
        'commission_pct',
        'commission_fcfa',
        'seller_net_fcfa',
        'accepted_at',
        'delivered_at',
        'confirmed_at',
        'auto_confirm_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'order_type' => OrderType::class,
            'status' => OrderStatus::class,
            'delivery_mode' => DeliveryMode::class,
            'subtotal_fcfa' => 'integer',
            'delivery_fcfa' => 'integer',
            'total_fcfa' => 'integer',
            'commission_pct' => 'decimal:2',
            'commission_fcfa' => 'integer',
            'seller_net_fcfa' => 'integer',
            'accepted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'auto_confirm_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function escrow()
    {
        return $this->hasOne(Escrow::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function dispute()
    {
        return $this->hasOne(Dispute::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
