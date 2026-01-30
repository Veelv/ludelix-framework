<?php

namespace Ludelix\Core\Console\Commands\Connect;

class ConnectHelper
{
    public static function createManualConfig(string $framework = 'react', array $options = []): array
    {
        return [
            'enabled' => true,
            'framework' => $framework,
            'entry_point' => 'frontend/js/app.js',
            'template' => 'frontend/templates/app.ludou',
            'build_path' => 'public/build',
            'dev_server' => [
                'host' => 'localhost',
                'port' => 5173
            ],
            'vite' => [
                'manifest' => 'public/build/manifest.json',
                'dev_server' => 'http://localhost:5173'
            ],
            'options' => $options
        ];
    }

    public static function getViteHelper(): string
    {
        return "
// Para usar manualmente:
// 1. Instale as dependências: npm install
// 2. Configure o vite.config.js
// 3. Execute: npm run dev
// 4. Para produção: npm run build

// Exemplo de uso no template:
// @vite(['frontend/css/app.css', 'frontend/js/app.js'])
";
    }
}