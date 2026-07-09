<?php

namespace App\Services;

class EmailPushService
{
    /**
     * Send email notification.
     */
    public function sendEmail(string $email, string $subject, string $body): bool
    {
        // TODO: Implement SMTP / Mailgun / SES email sending logic
        return true;
    }
}
