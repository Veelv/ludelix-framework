<?php

namespace Ludelix\Ludou\Core;

use Ludelix\Interface\Template\TemplateEngineInterface;
use Ludelix\Ludou\Cache\FileCache;
use Ludelix\Flash\Support\FlashHelper;

/**
 * SharpTemplate Engine
 * 
 * Main template engine for .ludou files with Sharp syntax (#[])
 */
class TemplateEngine implements TemplateEngineInterface
{
    protected TemplateCompiler $compiler;
    protected TemplateRenderer $renderer;
    protected FileCache $cache;
    protected array $paths = [];
    protected array $globals = [];
    protected array $functions = [];
    protected array $filters = [];

    public function __construct(array $paths = [], bool $cache = true)
    {
        $this->compiler = new TemplateCompiler();
        $this->renderer = new TemplateRenderer();
        $this->cache = new FileCache($cache);
        $this->paths = $paths;
        $this->registerDefaults();
    }

    public function render(string $template, array $data = []): string
    {
        try {
            $templatePath = $this->findTemplate($template);
            if (!$templatePath) {
                // Template não encontrado - retornar erro específico
                $searchedPaths = implode(', ', $this->paths);
                throw new \Exception("Template '{$template}' não encontrado. Procurado em: {$searchedPaths}");
            }

            $cacheKey = md5($templatePath . filemtime($templatePath));
            
            if ($this->cache->has($cacheKey)) {
                $compiled = $this->cache->get($cacheKey);
            } else {
                $content = file_get_contents($templatePath);
                $compiled = $this->compile($content);
                $this->cache->put($cacheKey, $compiled);
            }

            $this->renderer->setGlobals($this->globals);
            $this->renderer->setFunctions($this->functions);
            $this->renderer->setFilters($this->filters);

            $result = $this->renderer->render($compiled, $data);
            
            // Processar resultado final com LudouHook se disponível
            try {
                if (class_exists('\\Ludelix\\Bridge\\Bridge')) {
                    $bridge = \Ludelix\Bridge\Bridge::instance();
                    if ($bridge->has(\Ludelix\Fluid\Integration\LudouHook::class)) {
                        $fluidHook = $bridge->make(\Ludelix\Fluid\Integration\LudouHook::class);
                        if (\is_object($fluidHook) && \method_exists($fluidHook, 'afterRender')) {
                            $result = $fluidHook->afterRender($result);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Log error but continue
                try {
                    $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
                    if ($logger) {
                        $logger->error('[TemplateEngine] Error processing Fluid afterRender: ' . $e->getMessage());
                    }
                } catch (\Throwable $e) {}
            }
            
            // Debug: se resultado está vazio, retornar template compilado para debug
            if (empty(trim($result))) {
                $logger = null;
                try {
                    $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
                } catch (\Throwable $e) {}
                if ($logger) {
                    $logger->warning("Template '{$template}' rendered empty. Compiled: " . substr($compiled, 0, 500));
                }
            }
            
            return $result;
        } catch (\Throwable $e) {
            // Log via logger central do framework
            try {
                $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
                if ($logger) {
                    $logger->error('[TemplateEngine] ' . get_class($e) . ': ' . $e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } catch (\Throwable $logEx) {}
            
            throw $e;
        }
    }

    public function compile(string $template): string
    {
        return $this->compiler->compile($template, $this->functions, $this->filters);
    }

    public function exists(string $template): bool
    {
        return $this->findTemplate($template) !== null;
    }

    public function addPath(string $path): void
    {
        $this->paths[] = rtrim($path, '/');
    }

    public function addGlobal(string $key, mixed $value): void
    {
        $this->globals[$key] = $value;
    }

    public function addFunction(string $name, callable $callback): void
    {
        $this->functions[$name] = $callback;
    }

    public function addFilter(string $name, callable $callback): void
    {
        $this->filters[$name] = $callback;
    }

    protected function findTemplate(string $template): ?string
    {
        $template = str_replace('.', '/', $template);
        foreach ($this->paths as $path) {
            $fullPath = $path . '/' . $template . '.ludou';
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        return null;
    }

    protected function registerDefaults(): void
    {
        // Functions
        $this->functions['t'] = fn($key, $params = []) => str_replace(array_keys($params), array_values($params), $key);
        $this->functions['connect'] = fn($component, $props = []) => json_encode(['component' => $component, 'props' => $props]);
        $this->functions['asset'] = fn($path) => '/assets/' . ltrim($path, '/');
        $this->functions['service'] = fn($name) => app($name);
        $this->functions['config'] = fn($key, $default = null) => config($key, $default);
        $this->functions['flash'] = fn($type = null) => $type ? FlashHelper::renderType($type) : FlashHelper::render();

        // Filters
        $this->filters['upper'] = fn($value) => strtoupper($value);
        $this->filters['lower'] = fn($value) => strtolower($value);
        $this->filters['json'] = fn($value) => json_encode($value);
        $this->filters['escape'] = fn($value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $this->filters['raw'] = fn($value) => $value;
    }
}