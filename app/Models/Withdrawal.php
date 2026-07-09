<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory, HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'wallet_id',
        'amount_fcfa',
        'provider',
        'dest_phone',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => WithdrawalStatus::class,
            'amount_fcfa' => 'integer',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
