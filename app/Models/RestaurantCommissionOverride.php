<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantCommissionOverride extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = 'restaurant_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'restaurant_id',
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

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
