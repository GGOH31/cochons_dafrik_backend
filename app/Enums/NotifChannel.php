<?php

namespace App\Enums;

enum NotifChannel: string
{
    case SMS = 'sms';
    case PUSH = 'push';
}
