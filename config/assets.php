<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações de Assets
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de gerenciamento de assets do Ludelix.
    | Estas configurações controlam como os assets são servidos, versionados
    | e compilados.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Diretório Base dos Assets
    |--------------------------------------------------------------------------
    |
    | Diretório onde os assets estão armazenados fisicamente no servidor.
    | Este caminho é relativo ao diretório raiz da aplicação.
    |
    */
    'base_path' => env('ASSETS_BASE_PATH', 'public/assets'),

    /*
    |--------------------------------------------------------------------------
    | URL Base dos Assets
    |--------------------------------------------------------------------------
    |
    | URL base para acessar os assets via HTTP. Esta URL será prefixada
    | a todos os assets gerados pelo sistema.
    |
    */
    'base_url' => env('ASSETS_BASE_URL', '/assets'),

    /*
    |--------------------------------------------------------------------------
    | Versionamento de Assets
    |--------------------------------------------------------------------------
    |
    | Controla se os assets devem ser automaticamente versionados para
    | evitar problemas de cache. Pode ser 'query', 'path' ou false.
    |
    | - query: Adiciona ?v=timestamp na URL
    | - path: Inclui timestamp no nome do arquivo
    | - false: Desabilita versionamento
    |
    */
    'versioning' => env('ASSETS_VERSIONING', true),
    'versioning_strategy' => env('ASSETS_VERSIONING_STRATEGY', 'query'),

    /*
    |--------------------------------------------------------------------------
    | Manifesto de Assets
    |--------------------------------------------------------------------------
    |
    | Configurações para o manifesto que mapeia assets originais para
    | suas versões compiladas/versionadas.
    |
    */
    'manifest' => [
        'file' => env('ASSETS_MANIFEST_FILE', 'manifest.json'),
        'auto_generate' => env('ASSETS_AUTO_MANIFEST', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Mix
    |--------------------------------------------------------------------------
    |
    | Configurações para integração com Laravel Mix para compilação
    | e versionamento de assets.
    |
    */
    'mix' => [
        'manifest_file' => env('MIX_MANIFEST_FILE', 'mix-manifest.json'),
        'hot_file' => env('MIX_HOT_FILE', 'hot'),
        'public_path' => env('MIX_PUBLIC_PATH', 'public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compilação de Assets
    |--------------------------------------------------------------------------
    |
    | Configurações para compilação e minificação automática de assets.
    |
    */
    'compilation' => [
        'enabled' => env('ASSETS_COMPILATION_ENABLED', false),
        'minify_css' => env('ASSETS_MINIFY_CSS', true),
        'minify_js' => env('ASSETS_MINIFY_JS', true),
        'output_path' => env('ASSETS_COMPILED_PATH', 'compiled'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN
    |--------------------------------------------------------------------------
    |
    | Configurações para servir assets via CDN.
    |
    */
    'cdn' => [
        'enabled' => env('ASSETS_CDN_ENABLED', false),
        'url' => env('ASSETS_CDN_URL', ''),
        'assets' => [
            // Lista de assets que devem ser servidos via CDN
            'css/*',
            'js/*',
            'images/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configurações de cache para assets compilados e manifestos.
    |
    */
    'cache' => [
        'enabled' => env('ASSETS_CACHE_ENABLED', true),
        'ttl' => env('ASSETS_CACHE_TTL', 3600), // 1 hora
        'key_prefix' => env('ASSETS_CACHE_PREFIX', 'assets'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets Pré-definidos
    |--------------------------------------------------------------------------
    |
    | Assets que são registrados automaticamente no sistema.
    |
    */
    'assets' => [
        'app-css' => [
            'path' => 'css/app.css',
            'dependencies' => [],
            'attributes' => ['media' => 'all'],
        ],
        'app-js' => [
            'path' => 'js/app.js',
            'dependencies' => ['jquery'],
            'attributes' => ['defer' => true],
        ],
        'jquery' => [
            'path' => 'js/vendor/jquery.min.js',
            'dependencies' => [],
        ],
        'bootstrap-css' => [
            'path' => 'css/vendor/bootstrap.min.css',
            'dependencies' => [],
        ],
        'bootstrap-js' => [
            'path' => 'js/vendor/bootstrap.min.js',
            'dependencies' => ['jquery'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Grupos de Assets
    |--------------------------------------------------------------------------
    |
    | Grupos de assets que podem ser carregados juntos.
    |
    */
    'groups' => [
        'core' => [
            'jquery',
            'bootstrap-css',
            'bootstrap-js',
        ],
        'app' => [
            'app-css',
            'app-js',
        ],
        'admin' => [
            'core',
            'css/admin.css',
            'js/admin.js',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de URL
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para geração de URLs.
    |
    */
    'url' => [
        'force_https' => env('FORCE_HTTPS', false),
        'force_root_url' => env('FORCE_ROOT_URL', ''),
        'trailing_slash' => env('URL_TRAILING_SLASH', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Redirecionamento
    |--------------------------------------------------------------------------
    |
    | Configurações para o sistema de redirecionamento.
    |
    */
    'redirect' => [
        'default_status' => env('REDIRECT_DEFAULT_STATUS', 302),
        'preserve_query_string' => env('REDIRECT_PRESERVE_QUERY', true),
        'home_route' => env('REDIRECT_HOME_ROUTE', '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Desenvolvimento
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para ambiente de desenvolvimento.
    |
    */
    'development' => [
        'hot_reload' => env('ASSETS_HOT_RELOAD', false),
        'source_maps' => env('ASSETS_SOURCE_MAPS', true),
        'debug_mode' => env('ASSETS_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Produção
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para ambiente de produção.
    |
    */
    'production' => [
        'minification' => env('ASSETS_MINIFY_PRODUCTION', true),
        'compression' => env('ASSETS_COMPRESS_PRODUCTION', true),
        'cache_busting' => env('ASSETS_CACHE_BUST_PRODUCTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensões de Arquivo
    |--------------------------------------------------------------------------
    |
    | Mapeamento de extensões para tipos de asset.
    |
    */
    'extensions' => [
        'css' => ['css', 'scss', 'sass', 'less'],
        'js' => ['js', 'ts', 'jsx', 'tsx'],
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
        'fonts' => ['woff', 'woff2', 'ttf', 'eot', 'otf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware aplicado às rotas de assets.
    |
    */
    'middleware' => [
        'cache_headers',
        'compress',
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers HTTP
    |--------------------------------------------------------------------------
    |
    | Headers HTTP padrão para assets.
    |
    */
    'headers' => [
        'css' => [
            'Content-Type' => 'text/css',
            'Cache-Control' => 'public, max-age=31536000', // 1 ano
        ],
        'js' => [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'public, max-age=31536000', // 1 ano
        ],
        'images' => [
            'Cache-Control' => 'public, max-age=604800', // 1 semana
        ],
        'fonts' => [
            'Cache-Control' => 'public, max-age=31536000', // 1 ano
            'Access-Control-Allow-Origin' => '*',
        ],
    ],
];

