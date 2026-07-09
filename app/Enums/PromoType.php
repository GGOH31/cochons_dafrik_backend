<?php

namespace App\Enums;

enum PromoType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED_PRICE = 'fixed_price';
}
