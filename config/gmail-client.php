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

    /*
    |--------------------------------------------------------------------------
    | API Timeout and Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for API timeout, retry attempts, and error handling.
    |
    */
    'api' => [
        'timeout_seconds' => env('GMAIL_API_TIMEOUT', 30),
        'default_retry_after_seconds' => env('GMAIL_RETRY_AFTER', 60),
        'max_retry_attempts' => env('GMAIL_MAX_RETRIES', 3),
        'token_refresh_buffer_seconds' => env('GMAIL_TOKEN_BUFFER', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination and Result Limits
    |--------------------------------------------------------------------------
    |
    | Configuration for pagination, page sizes, and result limits.
    |
    */
    'pagination' => [
        'default_page_size' => env('GMAIL_DEFAULT_PAGE_SIZE', 100),
        'min_page_size' => env('GMAIL_MIN_PAGE_SIZE', 1),
        'max_page_size' => env('GMAIL_MAX_PAGE_SIZE', 500),
        'small_page_size' => env('GMAIL_SMALL_PAGE_SIZE', 10),
        'medium_page_size' => env('GMAIL_MEDIUM_PAGE_SIZE', 25),
        'large_page_size' => env('GMAIL_LARGE_PAGE_SIZE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message and Account Limits
    |--------------------------------------------------------------------------
    |
    | Configuration for message processing and account limitations.
    |
    */
    'limits' => [
        'unread_estimation_threshold' => env('GMAIL_UNREAD_THRESHOLD', 50),
        'today_message_limit' => env('GMAIL_TODAY_LIMIT', 15),
        'max_supported_accounts' => env('GMAIL_MAX_ACCOUNTS', 5),
        'message_batch_size' => env('GMAIL_BATCH_SIZE', 25),
        'max_email_size_mb' => env('GMAIL_MAX_EMAIL_SIZE', 25),
        'max_attachment_size_mb' => env('GMAIL_MAX_ATTACHMENT_SIZE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for caching and health checks.
    |
    */
    'cache' => [
        'ttl_seconds' => env('GMAIL_CACHE_TTL', 300),
        'health_check_interval_seconds' => env('GMAIL_HEALTH_CHECK_INTERVAL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Format Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for email content formatting and processing.
    |
    */
    'email' => [
        'default_format' => env('GMAIL_EMAIL_FORMAT', 'full'),
        'minimal_format' => 'minimal',
        'content_types' => [
            'json' => 'application/json',
            'html' => 'text/html; charset=utf-8',
            'plain' => 'text/plain; charset=utf-8',
            'multipart' => 'multipart/mixed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gmail System Labels
    |--------------------------------------------------------------------------
    |
    | Gmail system label IDs for common operations.
    |
    */
    'labels' => [
        'inbox' => 'INBOX',
        'starred' => 'STARRED',
        'unread' => 'UNREAD',
        'sent' => 'SENT',
        'draft' => 'DRAFT',
        'trash' => 'TRASH',
        'spam' => 'SPAM',
        'important' => 'IMPORTANT',
        'visibility' => [
            'show' => 'show',
            'hide' => 'hide',
            'label_show' => 'labelShow',
            'label_hide' => 'labelHide',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gmail Search Queries
    |--------------------------------------------------------------------------
    |
    | Pre-defined search query terms for common Gmail searches.
    |
    */
    'queries' => [
        'unread' => 'is:unread',
        'starred' => 'is:starred',
        'important' => 'is:important',
        'in_inbox' => 'in:inbox',
        'in_sent' => 'in:sent',
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Constants
    |--------------------------------------------------------------------------
    |
    | OAuth flow configuration and grant type constants.
    |
    */
    'oauth' => [
        'tokens' => [
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'expires_in' => 'expires_in',
        ],
        'grant_types' => [
            'authorization_code' => 'authorization_code',
            'refresh_token' => 'refresh_token',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Status Codes
    |--------------------------------------------------------------------------
    |
    | HTTP status codes used throughout the Gmail Client.
    |
    */
    'http_status' => [
        'success' => [
            'ok' => 200,
            'created' => 201,
            'no_content' => 204,
        ],
        'client_error' => [
            'bad_request' => 400,
            'unauthorized' => 401,
            'forbidden' => 403,
            'not_found' => 404,
            'conflict' => 409,
            'unprocessable_entity' => 422,
            'too_many_requests' => 429,
        ],
        'server_error' => [
            'internal_server_error' => 500,
            'service_unavailable' => 503,
        ],
    ],
];
