<?php

namespace App\Services;

class SmsPushService
{
    /**
     * Send SMS notification.
     */
    public function sendSms(string $phone, string $message): bool
    {
        // TODO: Implement SMS gateway logic (Twilio, Wave, Orange, etc.)
        return true;
    }
}
