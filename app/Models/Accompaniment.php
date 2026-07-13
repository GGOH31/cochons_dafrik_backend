<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accompaniment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'photo_url',
        'prix_unit',
    ];

    protected function casts(): array
    {
        return [
            'prix_unit' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
