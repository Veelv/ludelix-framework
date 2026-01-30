<?php

namespace Ludelix\Ludou\Core;

use Ludelix\Interface\Template\TemplateEngineInterface;
use Ludelix\Ludou\Cache\FileCache;
use Ludelix\Flash\Support\FlashHelper;

/**
 * SharpTemplate Engine
 * 
 * Main template engine for .ludou files with Sharp syntax (#[])
 * Handles template resolution, compilation, and rendering with a smart caching system.
 * 
 * @category Template
 * @package  Ludelix\Ludou\Core
 * @author   Ludelix Framework <contact@ludelix.com>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://ludelix.com
 */
class TemplateEngine implements TemplateEngineInterface
{
    /** @var TemplateCompiler Internal compiler instance */
    protected TemplateCompiler $compiler;

    /** @var TemplateRenderer Internal renderer instance */
    protected TemplateRenderer $renderer;

    /** @var FileCache Cache system instance */
    protected FileCache $cache;

    /** @var array List of template search paths */
    protected array $paths = [];

    /** @var array Global variables shared across all templates */
    protected array $globals = [];

    /** @var array Registered custom functions */
    protected array $functions = [];

    /** @var array Registered custom filters */
    protected array $filters = [];

    /**
     * Initialize the Template Engine.
     * 
     * @param array $paths   Optional search paths for templates
     * @param bool  $cache   Whether to enable template caching
     */
    public function __construct(array $paths = [], bool $cache = true)
    {
        $this->compiler = new TemplateCompiler();
        $this->renderer = new TemplateRenderer();
        $this->cache = new FileCache($cache);
        $this->paths = $paths;
        $this->registerDefaults();
    }

    /**
     * Render a template with provided data.
     * 
     * @param string $template Dot-notated template name (e.g., 'home.index')
     * @param array  $data     Data to be extracted into the template scope
     * @return string The rendered HTML content
     * @throws \Exception If template is not found or rendering fails
     */
    public function render(string $template, array $data = []): string
    {
        try {
            $templatePath = $this->findTemplate($template);
            if (!$templatePath) {
                // Template not found - attempt to find suggestions
                $searchedPaths = implode(', ', $this->paths);
                $suggestions = $this->findSimilarTemplates($template);

                $msg = "Template '{$template}' not found.\n";
                $msg .= "Searched in: {$searchedPaths}\n";

                if (!empty($suggestions)) {
                    $msg .= "Did you mean: '" . implode("' or '", $suggestions) . "'?";
                }

                throw new \Exception($msg);
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
                } catch (\Throwable $e) {
                }
            }

            // Debug: if result is empty, log warning with compiled template
            if (empty(trim($result))) {
                $logger = null;
                try {
                    $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
                } catch (\Throwable $e) {
                }
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
            } catch (\Throwable $logEx) {
            }

            throw $e;
        }
    }

    /**
     * Compile raw template string into PHP code.
     * 
     * @param string $template Raw template content
     * @return string The compiled PHP code
     */
    public function compile(string $template): string
    {
        return $this->compiler->compile($template, $this->functions, $this->filters);
    }

    /**
     * Check if a template file exists in any of the registered paths.
     * 
     * @param string $template Dot-notated template name
     * @return bool True if found, false otherwise
     */
    public function exists(string $template): bool
    {
        return $this->findTemplate($template) !== null;
    }

    /**
     * Add a base directory to the template search paths.
     * 
     * @param string $path Absolute or relative directory path
     */
    public function addPath(string $path): void
    {
        $this->paths[] = rtrim($path, '/');
    }

    /**
     * Register a global variable available to all templates.
     * 
     * @param string $key   Variable name
     * @param mixed  $value Variable value
     */
    public function addGlobal(string $key, mixed $value): void
    {
        $this->globals[$key] = $value;
    }

    /**
     * Register a custom function available within templates.
     * 
     * @param string   $name     Function handle (can be called via #[$name()])
     * @param callable $callback The implementation
     */
    public function addFunction(string $name, callable $callback): void
    {
        $this->functions[$name] = $callback;
    }

    /**
     * Register a custom filter for use with the pipe syntax.
     * 
     * @param string   $name     Filter handle (e.g., #[$var | $name])
     * @param callable $callback The filter logic
     */
    public function addFilter(string $name, callable $callback): void
    {
        $this->filters[$name] = $callback;
    }

    /**
     * Find the absolute path for a dot-notated template name.
     * 
     * @param string $template Dot-notated template (e.g., 'auth.login')
     * @return string|null The physical path or null if not found
     */
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

    /**
     * Register default helpers, functions, and filters.
     */
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

    /**
     * Search for similar template names to provide suggestions on failure.
     * Uses Levenshtein distance to find close matches.
     * 
     * @param string $missingTemplate The name of the template that was not found
     * @return array List of up to 3 suggested template names
     */
    protected function findSimilarTemplates(string $missingTemplate): array
    {
        $suggestions = [];
        $candidates = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path))
                continue;

            try {
                $dirIterator = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
                $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'ludou') {
                        // Get relative path
                        $relativePath = substr($file->getPathname(), strlen(realpath($path)) + 1);
                        // Normalize separators and remove extension
                        $name = str_replace([DIRECTORY_SEPARATOR, '/'], '.', $relativePath);
                        $name = preg_replace('/\.ludou$/', '', $name);

                        $candidates[] = $name;
                    }
                }
            } catch (\UnexpectedValueException $e) {
                // Ignore permission errors etc
                continue;
            }
        }

        // Find closest matches
        foreach (array_unique($candidates) as $candidate) {
            $lev = levenshtein($missingTemplate, $candidate);
            if ($lev <= 4) {
                $suggestions[$candidate] = $lev;
            } elseif (strpos($candidate, $missingTemplate) !== false || strpos($missingTemplate, $candidate) !== false) {
                $suggestions[$candidate] = 10; // Partial match
            }
        }

        asort($suggestions);
        return array_keys(array_slice($suggestions, 0, 3, true));
    }
}