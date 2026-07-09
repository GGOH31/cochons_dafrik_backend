<?php

namespace App\Services;

class FirebasePushService
{
    /**
     * Send push notification using Firebase Cloud Messaging.
     */
    public function sendPush(string $token, string $title, string $body, array $data = []): bool
    {
        // TODO: Implement FCM push notification sending logic
        return true;
    }
}
