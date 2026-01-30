<?php

namespace Ludelix\Core\Console\Commands\Framework;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Route Cache Command
 *
 * Gerenciar cache de rotas da aplicação
 */
class RouteCacheCommand extends BaseCommand
{
    protected string $signature = 'route:cache [--clear] [--rebuild]';
    protected string $description = 'Gerenciar cache de rotas da aplicação';

    public function execute(array $arguments, array $options): int
    {
        $clear = $this->option($options, 'clear', false);
        $rebuild = $this->option($options, 'rebuild', false);

        if ($clear) {
            return $this->clearCache();
        }

        if ($rebuild) {
            return $this->rebuildCache();
        }

        return $this->buildCache();
    }

    private function buildCache(): int
    {
        $this->info('🗂️  Gerando cache de rotas...');
        $this->line('');

        $cachePath = $this->getCachePath();
        $cacheDir = dirname($cachePath);

        try {
            // Criar diretório de cache se não existir
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
                $this->info("✓ Diretório de cache criado: {$cacheDir}");
            }

            // Obter todas as rotas
            $routes = $this->getAllRoutes();
            
            if (empty($routes)) {
                $this->error('⚠️  Nenhuma rota encontrada para cache!');
                return 1;
            }

            // Construir dados do cache
            $cacheData = [
                'routes' => $routes,
                'timestamp' => time(),
                'version' => '1.0',
                'generated_at' => date('Y-m-d H:i:s')
            ];

            // Escrever arquivo de cache
            $cacheContent = "<?php\n\nreturn " . var_export($cacheData, true) . ";\n";
            
            if (file_put_contents($cachePath, $cacheContent)) {
                $this->success('✓ Cache de rotas gerado com sucesso!');
                $this->line("📁 Arquivo: {$cachePath}");
                $this->line("📊 Rotas em cache: " . count($routes));
                $this->line("⏰ Gerado em: " . date('Y-m-d H:i:s'));
                return 0;
            } else {
                $this->error('❌ Falha ao escrever arquivo de cache!');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Erro ao gerar cache de rotas: ' . $e->getMessage());
            return 1;
        }
    }

    private function clearCache(): int
    {
        $this->info('🗑️  Limpando cache de rotas...');
        $this->line('');

        $cachePath = $this->getCachePath();

        if (file_exists($cachePath)) {
            if (unlink($cachePath)) {
                $this->success('✓ Cache de rotas limpo com sucesso!');
                $this->line("🗂️  Arquivo removido: {$cachePath}");
                return 0;
            } else {
                $this->error('❌ Falha ao limpar cache de rotas!');
                return 1;
            }
        } else {
            $this->info('ℹ️  Nenhum cache de rotas encontrado para limpar.');
            return 0;
        }
    }

    private function rebuildCache(): int
    {
        $this->info('🔄 Reconstruindo cache de rotas...');
        $this->line('');

        // Primeiro limpar o cache
        $clearResult = $this->clearCache();
        if ($clearResult !== 0) {
            return $clearResult;
        }

        $this->line('');
        
        // Depois construir o cache
        return $this->buildCache();
    }

    private function getCachePath(): string
    {
        $defaultPath = 'bootstrap/cache/routes.php';
        return dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/' . $defaultPath;
    }

    private function getAllRoutes(): array
    {
        $routes = [];
        
        // Tentar obter rotas do Bridge
        if (class_exists('Ludelix\\Bridge\\Bridge')) {
            $router = \Ludelix\Bridge\Bridge::route();
            if (method_exists($router, 'getRoutes')) {
                $routeObjects = $router->getRoutes();
                foreach ($routeObjects as $route) {
                    if (is_object($route) && method_exists($route, 'toArray')) {
                        $routes[] = $route->toArray();
                    }
                }
            }
        }
        
        // Fallback: ler arquivos de rotas manualmente
        if (empty($routes)) {
            $config = ['web' => 'routes/web.php'];
            foreach ($config as $name => $file) {
                $filePath = dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/' . $file;
                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    preg_match_all('/Bridge::route\(\)->(get|post|put|patch|delete|any)\s*\(\s*[\'"]([^\'"]+)[\'"][^)]*\)/i', $content, $matches, PREG_SET_ORDER);
                    foreach ($matches as $match) {
                        $routes[] = [
                            'methods' => [strtoupper($match[1])],
                            'path' => $match[2],
                            'handler' => 'Closure',
                            'name' => null,
                            'middleware' => []
                        ];
                    }
                }
            }
        }
        
        return $routes;
    }
} 