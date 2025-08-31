<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disk de Armazenamento Padrão
    |--------------------------------------------------------------------------
    |
    | Aqui você pode especificar o disk de armazenamento padrão que deve ser
    | usado pelo framework. O "local" é perfeito para desenvolvimento,
    | enquanto "s3" e outros são ideais para produção.
    |
    */

    'default' => env('STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Disks de Armazenamento
    |--------------------------------------------------------------------------
    |
    | Aqui você pode configurar quantos "disks" de armazenamento desejar,
    | e pode até configurar múltiplos disks do mesmo driver. Exemplos
    | para a maioria dos drivers suportados são configurados aqui.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => cubby_path('app/uploads'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'permissions' => [
                'file' => [
                    'public' => 0644,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0700,
                ],
            ],
        ],

        'public' => [
            'driver' => 'local',
            'root' => cubby_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'options' => [
                'ServerSideEncryption' => env('AWS_SERVER_SIDE_ENCRYPTION', 'AES256'),
                'StorageClass' => env('AWS_STORAGE_CLASS', 'STANDARD'),
            ],
            'throw' => false,
        ],

        'digitalocean' => [
            'driver' => 'digitalocean',
            'key' => env('DO_SPACES_KEY'),
            'secret' => env('DO_SPACES_SECRET'),
            'region' => env('DO_SPACES_REGION', 'nyc3'),
            'bucket' => env('DO_SPACES_BUCKET'),
            'endpoint' => env('DO_SPACES_ENDPOINT'),
            'url' => env('DO_SPACES_URL'),
            'cdn_url' => env('DO_SPACES_CDN_URL'),
            'visibility' => 'public',
        ],

        'cloudinary' => [
            'driver' => 'cloudinary',
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET'),
            'secure' => env('CLOUDINARY_SECURE', true),
            'transformations' => [
                'thumbnail' => [
                    'width' => 150,
                    'height' => 150,
                    'crop' => 'fill',
                    'quality' => 'auto',
                    'format' => 'auto',
                ],
                'small' => [
                    'width' => 300,
                    'height' => 300,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'format' => 'auto',
                ],
                'medium' => [
                    'width' => 600,
                    'height' => 600,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'format' => 'auto',
                ],
                'large' => [
                    'width' => 1200,
                    'height' => 1200,
                    'crop' => 'limit',
                    'quality' => 'auto',
                    'format' => 'auto',
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Validação
    |--------------------------------------------------------------------------
    |
    | Configurações globais de validação que serão aplicadas a todos os
    | uploads, a menos que sejam sobrescritas por configurações específicas.
    |
    */

    'validation' => [
        'max_file_size' => env('UPLOAD_MAX_FILE_SIZE', '10MB'),
        'allowed_mime_types' => [
            // Imagens
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            
            // Documentos
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
            
            // Vídeos
            'video/mp4',
            'video/webm',
            'video/quicktime',
            
            // Áudio
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
        ],
        'security_scan' => env('UPLOAD_SECURITY_SCAN', true),
        'extract_metadata' => env('UPLOAD_EXTRACT_METADATA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Upload
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para o processo de upload, incluindo
    | configurações de chunks, timeouts e processamento.
    |
    */

    'upload' => [
        'chunk_size' => env('UPLOAD_CHUNK_SIZE', 5 * 1024 * 1024), // 5MB
        'session_expiration_hours' => env('UPLOAD_SESSION_EXPIRATION', 24),
        'max_concurrent_uploads' => env('UPLOAD_MAX_CONCURRENT', 10),
        'timeout' => env('UPLOAD_TIMEOUT', 300), // 5 minutos
        'retry_attempts' => env('UPLOAD_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Thumbnails
    |--------------------------------------------------------------------------
    |
    | Configurações para geração automática de thumbnails para imagens.
    |
    */

    'thumbnails' => [
        'enabled' => env('THUMBNAILS_ENABLED', true),
        'driver' => env('THUMBNAILS_DRIVER', 'gd'), // gd, imagick
        'quality' => env('THUMBNAILS_QUALITY', 85),
        'sizes' => [
            'thumb' => '150x150',
            'small' => '300x300',
            'medium' => '600x600',
        ],
        'formats' => ['jpg', 'png', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Cache
    |--------------------------------------------------------------------------
    |
    | Configurações para cache de metadados e resultados de validação.
    |
    */

    'cache' => [
        'enabled' => env('STORAGE_CACHE_ENABLED', true),
        'ttl' => env('STORAGE_CACHE_TTL', 3600), // 1 hora
        'prefix' => env('STORAGE_CACHE_PREFIX', 'storage'),
        'store' => env('STORAGE_CACHE_STORE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Logging
    |--------------------------------------------------------------------------
    |
    | Configurações para logging de atividades de upload e storage.
    |
    */

    'logging' => [
        'enabled' => env('STORAGE_LOGGING_ENABLED', true),
        'level' => env('STORAGE_LOGGING_LEVEL', 'info'), // debug, info, warning, error
        'channels' => [
            'file' => env('STORAGE_LOG_FILE', true),
            'database' => env('STORAGE_LOG_DATABASE', false),
        ],
        'retention_days' => env('STORAGE_LOG_RETENTION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Monitoramento
    |--------------------------------------------------------------------------
    |
    | Configurações para coleta de métricas e monitoramento de performance.
    |
    */

    'monitoring' => [
        'enabled' => env('STORAGE_MONITORING_ENABLED', false),
        'metrics' => [
            'upload_times' => true,
            'file_sizes' => true,
            'error_rates' => true,
            'storage_usage' => true,
        ],
        'alerts' => [
            'error_threshold' => env('STORAGE_ERROR_THRESHOLD', 0.1), // 10%
            'storage_threshold' => env('STORAGE_USAGE_THRESHOLD', 0.8), // 80%
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Limpeza
    |--------------------------------------------------------------------------
    |
    | Configurações para limpeza automática de arquivos temporários e
    | uploads expirados.
    |
    */

    'cleanup' => [
        'enabled' => env('STORAGE_CLEANUP_ENABLED', true),
        'schedule' => env('STORAGE_CLEANUP_SCHEDULE', 'daily'),
        'temp_files_retention' => env('STORAGE_TEMP_RETENTION', 24), // horas
        'failed_uploads_retention' => env('STORAGE_FAILED_RETENTION', 7), // dias
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Segurança
    |--------------------------------------------------------------------------
    |
    | Configurações avançadas de segurança para uploads.
    |
    */

    'security' => [
        'scan_executables' => env('STORAGE_SCAN_EXECUTABLES', true),
        'check_magic_numbers' => env('STORAGE_CHECK_MAGIC_NUMBERS', true),
        'quarantine_suspicious' => env('STORAGE_QUARANTINE_SUSPICIOUS', false),
        'virus_scan' => [
            'enabled' => env('STORAGE_VIRUS_SCAN', false),
            'service' => env('STORAGE_VIRUS_SCAN_SERVICE', 'clamav'),
            'endpoint' => env('STORAGE_VIRUS_SCAN_ENDPOINT'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações por Tipo de Arquivo
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para diferentes tipos de arquivo.
    |
    */

    'file_types' => [
        'images' => [
            'max_size' => '5MB',
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'generate_thumbnails' => true,
            'extract_exif' => true,
            'strip_metadata' => false,
        ],
        'documents' => [
            'max_size' => '50MB',
            'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'],
            'extract_metadata' => true,
            'virus_scan' => true,
        ],
        'videos' => [
            'max_size' => '500MB',
            'allowed_extensions' => ['mp4', 'webm', 'mov', 'avi'],
            'generate_thumbnails' => true,
            'extract_metadata' => true,
            'chunk_upload' => true,
        ],
        'audio' => [
            'max_size' => '100MB',
            'allowed_extensions' => ['mp3', 'wav', 'ogg', 'flac'],
            'extract_metadata' => true,
        ],
    ],

];

