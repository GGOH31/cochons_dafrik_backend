<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'category_id',
        'name',
        'description',
        'photo_url',
        'unit',
        'price_fcfa',
        'prep_minutes',
        'stock_qty',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_fcfa' => 'integer',
            'prep_minutes' => 'integer',
            'stock_qty' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
