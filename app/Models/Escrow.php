<?php

namespace App\Models;

use App\Enums\EscrowStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    use HasFactory, HasUuids;

    const CREATED_AT = 'held_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'payment_id',
        'amount_fcfa',
        'status',
        'released_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EscrowStatus::class,
            'amount_fcfa' => 'integer',
            'held_at' => 'datetime',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
