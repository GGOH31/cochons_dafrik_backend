<?php

namespace App\Enums;

enum EscrowStatus: string
{
    case HELD = 'held';
    case RELEASED = 'released';
    case REFUNDED = 'refunded';
    case FROZEN = 'frozen';
}
