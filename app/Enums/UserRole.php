<?php

namespace App\Enums;

enum UserRole: string
{
    case CLIENT = 'client';
    case VENDEUR = 'vendeur';
    case GROSSISTE = 'grossiste';
    case ADMIN = 'admin';
}
