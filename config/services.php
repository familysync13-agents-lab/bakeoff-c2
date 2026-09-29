<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Book-search API (Open Library /search.json format), called server-side only.
    'book_api' => [
        'base_url' => env('BOOK_API_BASE_URL'),
    ],

    // Browser error monitoring (public values only: rendered into every page).
    'sentry_browser' => [
        'dsn' => env('PUBLIC_SENTRY_DSN'),
    ],

    // Share-link signing key (HMAC-SHA256). Server-side secret: never render it or share it with the browser.
    'share_links' => [
        'key' => env('V0_SECRET_CANARY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
