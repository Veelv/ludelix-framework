<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Ludelix',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => (bool) ($_ENV['APP_DEBUG'] ?? false),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    'locale' => $_ENV['APP_LOCALE'] ?? 'en',
    'fallback_locale' => $_ENV['APP_FALLBACK_LOCALE'] ?? 'en',
    'key' => $_ENV['APP_KEY'] ?? null,
    'cipher' => 'AES-256-CBC',
];