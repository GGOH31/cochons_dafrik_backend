<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'dish_id',
        'dish_name',
        'unit_price_fcfa',
        'quantity',
        'options',
        'line_total_fcfa',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_fcfa' => 'integer',
            'quantity' => 'decimal:2',
            'options' => 'array',
            'line_total_fcfa' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function dish()
    {
        return $this->belongsTo(Dish::class, 'dish_id');
    }
}
