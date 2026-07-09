<?php

namespace App\Models;

use App\Enums\NotifChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'order_id',
        'channel',
        'title',
        'body',
        'sent_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotifChannel::class,
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
