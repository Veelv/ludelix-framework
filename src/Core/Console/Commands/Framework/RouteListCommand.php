<?php

namespace Ludelix\Core\Console\Commands\Framework;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Route List Command
 *
 * Lista todas as rotas registradas na aplicação
 */
class RouteListCommand extends BaseCommand
{
    protected string $signature = 'route:list [--method=] [--path=] [--name=]';
    protected string $description = 'Lista todas as rotas registradas na aplicação';

    public function execute(array $arguments, array $options): int
    {
        $methodFilter = $this->option($options, 'method', '');
        $pathFilter = $this->option($options, 'path', '');
        $nameFilter = $this->option($options, 'name', '');

        $routes = $this->getRoutes();

        if (empty($routes)) {
            $this->warning('⚠️  Nenhuma rota encontrada.');
            return 0;
        }

        // Aplicar filtros
        $routes = $this->filterRoutes($routes, $methodFilter, $pathFilter, $nameFilter);

        if (empty($routes)) {
            $this->warning('⚠️  Nenhuma rota encontrada com os filtros especificados.');
            return 0;
        }

        $this->displayRoutes($routes);
        $this->displaySummary($routes);

        return 0;
    }

    private function getRoutes(): array
    {
        // Tenta obter as rotas do Bridge, se disponível
        if (class_exists('Ludelix\\Bridge\\Bridge')) {
            $router = \Ludelix\Bridge\Bridge::route();
            if (method_exists($router, 'getRoutes')) {
                return $router->getRoutes();
            }
        }
        
        // Fallback: tenta ler o arquivo de rotas manualmente
        $routes = [];
        $config = require base_path('config/routes.php');
        $files = $config['files'] ?? ['web' => 'routes/web.php'];
        foreach ($files as $file) {
            $filePath = base_path($file);
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                preg_match_all('/Bridge::route\(\)->(get|post|put|patch|delete|any)\\s*\(\\s*[\'\"]([^\'\"]+)[\'\"][^)]*\)/i', $content, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $routes[] = [
                        'method' => strtoupper($match[1]),
                        'path' => $match[2],
                        'handler' => 'Closure',
                    ];
                }
            }
        }
        return $routes;
    }

    private function filterRoutes(array $routes, string $methodFilter, string $pathFilter, string $nameFilter): array
    {
        return array_filter($routes, function($route) use ($methodFilter, $pathFilter, $nameFilter) {
            if ($methodFilter && is_object($route) && method_exists($route, 'getMethods')) {
                $methods = $route->getMethods();
                if (!in_array(strtoupper($methodFilter), $methods)) {
                    return false;
                }
            } elseif ($methodFilter && is_array($route)) {
                if (strtoupper($route['method']) !== strtoupper($methodFilter)) {
                    return false;
                }
            }

            if ($pathFilter) {
                $path = is_object($route) ? $route->getPath() : $route['path'];
                if (strpos($path, $pathFilter) === false) {
                    return false;
                }
            }

            if ($nameFilter && is_object($route) && method_exists($route, 'getName')) {
                $name = $route->getName();
                if (!$name || strpos($name, $nameFilter) === false) {
                    return false;
                }
            }

            return true;
        });
    }

    private function displayRoutes(array $routes): void
    {
        $this->info('🗺️  Rotas Registradas');
        $this->line('');

        // Cabeçalho da tabela
        $this->line('┌─────────────┬──────────────────────────────────────┬──────────────────────┬─────────────┐');
        $this->line('│ Método      │ URI                                 │ Handler              │ Middleware  │');
        $this->line('├─────────────┼──────────────────────────────────────┼──────────────────────┼─────────────┤');

        foreach ($routes as $route) {
            if (is_object($route) && method_exists($route, 'getMethods')) {
                $methods = implode('|', $route->getMethods());
                $path = $route->getPath();
                $handler = $this->formatHandler($route->getHandler());
                $middleware = $this->formatMiddleware($route->getMiddleware());
                $name = $route->getName();
            } elseif (is_array($route)) {
                $methods = $route['method'];
                $path = $route['path'];
                $handler = $route['handler'];
                $middleware = '';
                $name = null;
            } else {
                continue;
            }

            // Formatar linha da tabela
            $methodCol = str_pad($methods, 11);
            $pathCol = str_pad(substr($path, 0, 36), 36);
            $handlerCol = str_pad(substr($handler, 0, 20), 20);
            $middlewareCol = str_pad(substr($middleware, 0, 11), 11);

            $this->line("│ {$methodCol} │ {$pathCol} │ {$handlerCol} │ {$middlewareCol} │");

            // Mostrar nome da rota se existir
            if ($name) {
                $nameLine = str_pad("   Nome: {$name}", 85);
                $this->line("│ {$nameLine} │");
            }
        }

        $this->line('└─────────────┴──────────────────────────────────────┴──────────────────────┴─────────────┘');
    }

    private function formatHandler(mixed $handler): string
    {
        if (is_string($handler)) {
            return $handler;
        } elseif (is_array($handler)) {
            return json_encode($handler);
        } elseif (is_object($handler)) {
            return get_class($handler);
        } else {
            return 'Closure';
        }
    }

    private function formatMiddleware(array $middleware): string
    {
        if (empty($middleware)) {
            return '';
        }
        return implode(', ', array_slice($middleware, 0, 2)) . (count($middleware) > 2 ? '...' : '');
    }

    private function displaySummary(array $routes): void
    {
        $this->line('');
        $this->info('📊 Resumo');
        $this->line('');

        $totalRoutes = count($routes);
        $methods = [];
        $namedRoutes = 0;

        foreach ($routes as $route) {
            if (is_object($route)) {
                $routeMethods = $route->getMethods();
                foreach ($routeMethods as $method) {
                    $methods[$method] = ($methods[$method] ?? 0) + 1;
                }
                if ($route->getName()) {
                    $namedRoutes++;
                }
            } elseif (is_array($route)) {
                $methods[$route['method']] = ($methods[$route['method']] ?? 0) + 1;
            }
        }

        $this->line("   Total de rotas: {$totalRoutes}");
        $this->line("   Rotas nomeadas: {$namedRoutes}");
        $this->line("   Métodos HTTP:");
        
        foreach ($methods as $method => $count) {
            $this->line("     {$method}: {$count}");
        }
    }
} 