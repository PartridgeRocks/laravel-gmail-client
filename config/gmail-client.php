<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gmail API Credentials
    |--------------------------------------------------------------------------
    |
    | Your Gmail API client ID and client secret, obtained from the
    | Google Developer Console.
    |
    */
    'client_id' => env('GMAIL_CLIENT_ID'),
    'client_secret' => env('GMAIL_CLIENT_SECRET'),
    'redirect_uri' => env('GMAIL_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Gmail API Scopes
    |--------------------------------------------------------------------------
    |
    | The scopes requested when authenticating with Google.
    | See https://developers.google.com/gmail/api/auth/scopes for available scopes.
    |
    */
    'scopes' => [
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/gmail.send',
        'https://www.googleapis.com/auth/gmail.compose',
        'https://www.googleapis.com/auth/gmail.modify',
        'https://www.googleapis.com/auth/gmail.labels',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default From Email
    |--------------------------------------------------------------------------
    |
    | The default email to use as the sender when sending emails.
    |
    */
    'from_email' => env('GMAIL_FROM_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Store Tokens in Database
    |--------------------------------------------------------------------------
    |
    | Whether to store access tokens in the database.
    |
    */
    'store_tokens' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto Refresh Tokens
    |--------------------------------------------------------------------------
    |
    | Whether to automatically refresh tokens when they expire.
    |
    */
    'auto_refresh_tokens' => true,

    /*
    |--------------------------------------------------------------------------
    | Token Cache Time
    |--------------------------------------------------------------------------
    |
    | Time in minutes to cache tokens.
    |
    */
    'token_cache_time' => 60,

    /*
    |--------------------------------------------------------------------------
    | Branded Email Template
    |--------------------------------------------------------------------------
    |
    | Path to a custom branded email template to use when sending emails.
    | Leave null to use the default Gmail template.
    |
    */
    'branded_template' => null,

    /*
    |--------------------------------------------------------------------------
    | Auto Register Routes
    |--------------------------------------------------------------------------
    |
    | Whether to automatically register routes for authentication.
    | If true, this will register routes for OAuth redirect and callback.
    |
    */
    'register_routes' => env('GMAIL_REGISTER_ROUTES', false),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for the routes registered by this package.
    | Routes will be registered as: /{prefix}/auth/redirect and /{prefix}/auth/callback
    |
    */
    'route_prefix' => 'gmail',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware to apply to the routes registered by this package.
    | Typically you'd want to include 'web' for session support and any
    | additional middleware like 'auth' if needed.
    |
    */
    'route_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimizations and batch operations.
    |
    */
    'performance' => [
        'enable_smart_counting' => env('GMAIL_SMART_COUNTING', true),
        'count_estimation_threshold' => env('GMAIL_COUNT_THRESHOLD', 50),
        'default_cache_ttl' => env('GMAIL_CACHE_TTL', 300),
        'max_concurrent_requests' => env('GMAIL_MAX_CONCURRENT', 3),
        'enable_circuit_breaker' => env('GMAIL_CIRCUIT_BREAKER', true),
        'api_timeout' => env('GMAIL_API_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Account Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for managing multiple Gmail accounts per user.
    |
    */
    'multi_account' => [
        'max_accounts_per_user' => env('GMAIL_MAX_ACCOUNTS', 5),
        'health_check_interval' => env('GMAIL_HEALTH_CHECK_INTERVAL', 3600),
        'enable_bulk_operations' => env('GMAIL_BULK_OPERATIONS', true),
    ],
];
