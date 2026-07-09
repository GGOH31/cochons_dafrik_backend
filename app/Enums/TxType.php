<?php

namespace App\Enums;

enum TxType: string
{
    case ESCROW_HOLD = 'escrow_hold';
    case ESCROW_RELEASE = 'escrow_release';
    case COMMISSION = 'commission';
    case REFUND = 'refund';
    case WITHDRAWAL = 'withdrawal';
    case ADJUSTMENT = 'adjustment';
}
