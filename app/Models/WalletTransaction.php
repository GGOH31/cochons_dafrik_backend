<?php

namespace App\Models;

use App\Enums\TxType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'wallet_id',
        'order_id',
        'tx_type',
        'amount_fcfa',
        'balance_after',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'tx_type' => TxType::class,
            'amount_fcfa' => 'integer',
            'balance_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
