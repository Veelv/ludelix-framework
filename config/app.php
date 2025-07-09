<?php

return [
    'name' => env('APP_NAME', 'Ludelix'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    
    // Security Configuration
    'security' => [
        'csrf' => [
            'enabled' => env('CSRF_ENABLED', true),
            'token_name' => '_token',
            'expire_time' => env('CSRF_EXPIRE', 3600),
        ],
        'encryption' => [
            'key' => env('APP_KEY'),
            'cipher' => 'AES-256-CBC',
        ],
    ],
    
    // Asset Configuration
    'assets' => [
        'driver' => env('ASSET_DRIVER', 'vite'),
        'vite' => [
            'dev_server_url' => env('VITE_DEV_SERVER_URL', 'http://localhost:5173'),
            'build_path' => 'build',
        ],
    ],
    
    // WebSocket Configuration
    'websocket' => [
        'enabled' => env('WEBSOCKET_ENABLED', false),
        'host' => env('WEBSOCKET_HOST', '0.0.0.0'),
        'port' => env('WEBSOCKET_PORT', 6001),
        'ssl' => [
            'enabled' => env('WEBSOCKET_SSL', false),
            'cert' => env('WEBSOCKET_SSL_CERT'),
            'key' => env('WEBSOCKET_SSL_KEY'),
        ],
    ],
    
    // Multi-tenancy Configuration
    'tenant' => [
        'enabled' => env('TENANT_ENABLED', false),
        'identification' => [
            'domain' => env('TENANT_DOMAIN', true),
            'subdomain' => env('TENANT_SUBDOMAIN', true),
            'header' => env('TENANT_HEADER', 'X-Tenant'),
        ],
        'database' => [
            'separate_databases' => env('TENANT_SEPARATE_DB', false),
            'prefix_tables' => env('TENANT_PREFIX_TABLES', true),
        ],
    ],
];