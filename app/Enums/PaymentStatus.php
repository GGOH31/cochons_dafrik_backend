<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case INITIATED = 'initiated';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
}
