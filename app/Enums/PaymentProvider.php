<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case ORANGE_MONEY = 'orange_money';
    case MTN_MOMO = 'mtn_momo';
    case MOOV_MONEY = 'moov_money';
    case WAVE = 'wave';
    case CARD = 'card';
}
