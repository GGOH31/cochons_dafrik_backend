<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'status',
        'changed_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
