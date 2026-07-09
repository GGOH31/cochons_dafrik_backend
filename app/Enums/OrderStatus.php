<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case PAID = 'paid';
    case ACCEPTED = 'accepted';
    case PREPARING = 'preparing';
    case DELIVERING = 'delivering';
    case DELIVERED = 'delivered';
    case COMPLETED = 'completed';
    case REFUSED = 'refused';
    case CANCELLED = 'cancelled';
    case DISPUTED = 'disputed';
}
