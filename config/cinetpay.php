<?php

return [
    'base_url' => env('CINETPAY_BASE_URL', 'https://api.cinetpay.net'),

    'api_key' => env('CINETPAY_API_KEY'),
    'api_password' => env('CINETPAY_API_PASSWORD'),

    'currency' => env('CINETPAY_CURRENCY', 'XOF'),
    'lang' => env('CINETPAY_LANG', 'fr'),

    // Path used to re-verify a payment's status server-side (GET {base_url}/v1/{check_status_path}/{transaction_id}).
    // CinetPay's docs only showed the equivalent endpoint for transfers (/v1/transfer/{transaction_id}); confirm
    // the payment one in the CinetPay dashboard/docs once IP whitelisting allows live testing, and adjust if needed.
    'check_status_path' => env('CINETPAY_CHECK_STATUS_PATH', 'payment'),

    'success_url' => env('CINETPAY_SUCCESS_URL'),
    'failed_url' => env('CINETPAY_FAILED_URL'),
    'notify_url' => env('CINETPAY_NOTIFY_URL'),
];
