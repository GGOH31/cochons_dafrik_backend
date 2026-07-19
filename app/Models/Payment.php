<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'provider',
        'provider_ref',
        'amount_fcfa',
        'status',
        'payload',
        'paid_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentStatus::class,
            'amount_fcfa' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function escrow()
    {
        return $this->hasOne(Escrow::class);
    }
}
