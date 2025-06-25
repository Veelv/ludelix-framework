<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Driver
    |--------------------------------------------------------------------------
    */
    'default' => env('CACHE_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cache Drivers
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'file' => [
            'enabled' => true,
            'path' => storage_path('cache'),
            'ttl' => 3600,
        ],

        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_CACHE_DB', 1),
            'prefix' => env('CACHE_PREFIX', 'ludelix:'),
            'ttl' => 3600,
        ],

        'database' => [
            'connection' => env('DB_CONNECTION', 'default'),
            'table' => 'cache',
            'ttl' => 3600,
        ],

        'memory' => [
            'max_size' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Prefixes
    |--------------------------------------------------------------------------
    */
    'prefix' => env('CACHE_PREFIX', 'ludelix'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Cache Isolation
    |--------------------------------------------------------------------------
    */
    'tenant_isolation' => env('CACHE_TENANT_ISOLATION', true),

    /*
    |--------------------------------------------------------------------------
    | Auto Cleanup
    |--------------------------------------------------------------------------
    */
    'auto_cleanup' => [
        'enabled' => true,
        'probability' => 2, // 2% chance on each request
        'divisor' => 100,
    ],
];