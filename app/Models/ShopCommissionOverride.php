<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopCommissionOverride extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = 'shop_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'shop_id',
        'rate_pct',
        'updated_by',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'rate_pct' => 'decimal:2',
            'updated_at' => 'datetime',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
