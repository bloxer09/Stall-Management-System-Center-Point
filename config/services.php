<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', env('APP_URL').'/auth/facebook/callback'),
    ],

    'semaphore' => [
        'api_key' => env('SEMAPHORE_API_KEY'),
        'sender_name' => env('SEMAPHORE_SENDER_NAME', 'STALLM'),
    ],

    'mailjet' => [
        'api_key' => env('MAILJET_API_KEY'),
        'secret_key' => env('MAILJET_SECRET_KEY'),
        'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@stallm.com'),
        'from_name' => env('MAIL_FROM_NAME', 'Center Point Stall Management'),
    ],

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_TOKEN'),
    ],

    'enable_2fa' => env('ENABLE_2FA', false),
];
