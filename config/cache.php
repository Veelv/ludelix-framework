<?php

return [
    'default' => env('CACHE_DRIVER', 'file'),
    
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => $_ENV['STORAGE_PATH'] ?? './cubby/cache',
        ],
        
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
        ],
        
        'memory' => [
            'driver' => 'memory',
        ],
    ],
    
    'prefix' => env('CACHE_PREFIX', 'ludelix_cache'),
];