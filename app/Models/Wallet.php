<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory, HasUuids;

    const CREATED_AT = null;

    protected $fillable = [
        'shop_id',
        'balance_fcfa',
    ];

    protected function casts(): array
    {
        return [
            'balance_fcfa' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }
}
