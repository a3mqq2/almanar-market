<?php

return [
    'mode' => env('DESKTOP_MODE', false),

    'device_id' => env('DEVICE_ID'),

    'server_url' => env('SYNC_SERVER_URL', 'https://your-server.com'),

    'api_token' => env('DEVICE_API_TOKEN'),

    'sync_interval' => env('SYNC_INTERVAL', 300),

    'sync_on_startup' => env('SYNC_ON_STARTUP', true),

    'sync_on_reconnect' => env('SYNC_ON_RECONNECT', true),

    'max_retry_attempts' => env('SYNC_MAX_RETRIES', 5),

    'retry_delay' => env('SYNC_RETRY_DELAY', 60),

    'batch_size' => env('SYNC_BATCH_SIZE', 100),

    'encryption' => [
        'enabled' => env('SQLITE_ENCRYPTION_ENABLED', false),
        'key' => env('SQLITE_ENCRYPTION_KEY'),
    ],

    'license' => [
        'key' => env('LICENSE_KEY'),
        'validation_url' => env('LICENSE_VALIDATION_URL'),
    ],

    'app' => [
        'version' => env('DESKTOP_APP_VERSION', '1.0.0'),
        'auto_update' => env('DESKTOP_AUTO_UPDATE', true),
        'update_url' => env('DESKTOP_UPDATE_URL'),
    ],
];
