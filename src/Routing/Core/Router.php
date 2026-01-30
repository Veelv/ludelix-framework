<?php

namespace Ludelix\Routing\Core;

use Ludelix\Interface\Routing\RouterInterface;
use Ludelix\Interface\Routing\RouteInterface;
use Ludelix\Interface\Routing\RouteGroupInterface;
use Ludelix\Routing\Core\Route;
use Ludelix\Routing\Core\RouteGroup;
use Ludelix\Routing\Core\RouteCollection;
use Ludelix\Routing\Parsers\YamlRouteParser;
use Ludelix\Routing\Parsers\PhpRouteParser;
use Ludelix\Routing\Parsers\JsonRouteParser;

use Ludelix\Routing\Compilers\RouteCompiler;
use Ludelix\Routing\Cache\RouteCache;
use Ludelix\Routing\Resolvers\RouteResolver;
use Ludelix\Routing\Generators\UrlGenerator;
use Ludelix\Routing\Events\RouteMatchedEvent;
use Ludelix\Routing\Events\RouteRegisteredEvent;
use Ludelix\Routing\Exceptions\RouteNotFoundException;
use Ludelix\Routing\Exceptions\MethodNotAllowedException;
use Ludelix\Routing\Exceptions\RouteCompilationException;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
use Ludelix\Core\EventDispatcher;
use Ludelix\Cache\CacheManager;
use Ludelix\Tenant\Core\TenantManager;
use Ludelix\Interface\Logging\LoggerInterface;

/**
 * Router - Sistema de Roteamento Completo
 */
class Router implements RouterInterface
{
    protected RouteCollection $routes;
    protected RouteCompiler $compiler;
    protected RouteCache $cache;
    protected RouteResolver $resolver;
    protected UrlGenerator $urlGenerator;
    protected EventDispatcher $eventDispatcher;
    protected LoggerInterface $logger;
    protected TenantManager $tenantManager;

    protected YamlRouteParser $yamlParser;
    protected PhpRouteParser $phpParser;
    protected JsonRouteParser $jsonParser;


    protected array $groupStack = [];
    protected array $currentAttributes = [];
    protected array $globalMiddleware = [];
    protected array $patterns = [];
    protected array $modelBindings = [];

    protected array $config = [];
    protected bool $cachingEnabled = true;
    protected bool $compilationEnabled = true;
    protected string $namespace = '';
    protected string $prefix = '';
    protected string $domain = '';

    protected array $performanceMetrics = [];
    protected array $routeUsageStats = [];
    protected int $totalRouteMatches = 0;
    protected float $totalMatchingTime = 0.0;
    protected array $slowRoutes = [];

    protected array $securityEvents = [];
    protected array $rateLimitingRules = [];
    protected bool $csrfProtectionEnabled = true;
    protected bool $corsEnabled = true;

    public function __construct(
        RouteCollection $routes,
        RouteCompiler $compiler,
        RouteCache $cache,
        RouteResolver $resolver,
        UrlGenerator $urlGenerator,
        EventDispatcher $eventDispatcher,
        LoggerInterface $logger,
        TenantManager $tenantManager,
        array $config = []
    ) {
        $this->routes = $routes;
        $this->compiler = $compiler;
        $this->cache = $cache;
        $this->resolver = $resolver;
        $this->urlGenerator = $urlGenerator;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->tenantManager = $tenantManager;
        $this->config = $config;

        $this->initializeParsers();
        $this->initializeConfiguration();
        $this->registerDefaultPatterns();
    }

    public function get(string $path, mixed $handler, array $options = []): RouteInterface
    {
        $path = '/' . ltrim($path, '/');
        return $this->addRoute(['GET'], $path, $handler, $options);
    }

    public function post(string $path, mixed $handler, array $options = []): RouteInterface
    {
        $path = '/' . ltrim($path, '/');
        return $this->addRoute(['POST'], $path, $handler, $options);
    }

    public function put(string $path, mixed $handler, array $options = []): RouteInterface
    {
        $path = '/' . ltrim($path, '/');
        return $this->addRoute(['PUT'], $path, $handler, $options);
    }

    public function patch(string $path, mixed $handler, array $options = []): RouteInterface
    {
        $path = '/' . ltrim($path, '/');
        return $this->addRoute(['PATCH'], $path, $handler, $options);
    }

    public function delete(string $path, mixed $handler, array $options = []): RouteInterface
    {
        $path = '/' . ltrim($path, '/');
        return $this->addRoute(['DELETE'], $path, $handler, $options);
    }

    public function match(array $methods, string $path, mixed $handler, array $options = []): RouteInterface
    {
        $path = '/' . ltrim($path, '/');
        return $this->addRoute($methods, $path, $handler, $options);
    }

    public function any(string $path, mixed $handler, array $options = []): RouteInterface
    {
        $path = '/' . ltrim($path, '/');
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $path, $handler, $options);
    }

    public function websocket(string $path, mixed $handler, array $options = []): RouteInterface
    {
        $options['protocol'] = 'websocket';
        return $this->addRoute(['WEBSOCKET'], $path, $handler, $options);
    }

    public function graphql(string $path, mixed $handler, array $options = []): RouteInterface
    {
        $options['protocol'] = 'graphql';
        return $this->addRoute(['POST', 'GET'], $path, $handler, $options);
    }

    public function sse(string $path, mixed $handler, array $options = []): RouteInterface
    {
        $options['protocol'] = 'sse';
        return $this->addRoute(['GET'], $path, $handler, $options);
    }

    public function resource(string $name, string $controller, array $options = []): RouteGroupInterface
    {
        $group = new RouteGroup(['resource' => $name]);

        $actions = $options['only'] ?? ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'];
        $except = $options['except'] ?? [];
        $actions = array_diff($actions, $except);

        $resourceRoutes = [
            'index' => ['GET', "/{$name}", '@index'],
            'create' => ['GET', "/{$name}/create", '@create'],
            'store' => ['POST', "/{$name}", '@store'],
            'show' => ['GET', "/{$name}/{{$name}}", '@show'],
            'edit' => ['GET', "/{$name}/{{$name}}/edit", '@edit'],
            'update' => ['PUT', "/{$name}/{{$name}}", '@update'],
            'destroy' => ['DELETE', "/{$name}/{{$name}}", '@destroy'],
        ];

        foreach ($actions as $action) {
            if (isset($resourceRoutes[$action])) {
                [$method, $path, $handler] = $resourceRoutes[$action];
                $route = $this->addRoute([$method], $path, $controller . $handler)
                    ->name("{$name}.{$action}");
                $group->addRoute($route);
            }
        }

        return $group;
    }

    public function apiResource(string $name, string $controller, array $options = []): RouteGroupInterface
    {
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);
        return $this->resource($name, $controller, $options);
    }

    public function group(array $attributes, callable $callback): RouteGroupInterface
    {
        $this->groupStack[] = $attributes;
        $this->updateCurrentAttributes();

        $group = new RouteGroup($attributes);

        try {
            $callback($this);
        } finally {
            array_pop($this->groupStack);
            $this->updateCurrentAttributes();
        }

        return $group;
    }

    public function tenant(array $tenantConfig, callable $callback): RouteGroupInterface
    {
        return $this->group(['tenant' => $tenantConfig], $callback);
    }

    public function version(string $version, callable $callback, array $options = []): RouteGroupInterface
    {
        return $this->group(array_merge(['version' => $version], $options), $callback);
    }

    public function when(array $conditions, callable $callback): RouteGroupInterface
    {
        return $this->group(['conditions' => $conditions], $callback);
    }

    public function loadFromYaml(string $filePath, array $options = []): self
    {
        $this->yamlParser->parseFile($filePath, $this);
        return $this;
    }

    public function loadFromPhp(string $filePath, array $options = []): self
    {
        $this->phpParser->parseFile($filePath, $this);
        return $this;
    }

    public function loadFromJson(string $json, array $options = []): self
    {
        $this->jsonParser->parse($json, $this);
        return $this;
    }

    public function loadFromDatabase(array $criteria = [], array $options = []): self
    {
        // Database parser not implemented yet
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $startTime = microtime(true);

        try {
            $routeInfo = $this->resolve($request);

            if ($routeInfo['status'] === 'not_found') {
                throw new RouteNotFoundException(
                    "No route found for {$request->getMethod()} {$request->getPath()}"
                );
            }

            if ($routeInfo['status'] === 'method_not_allowed') {
                throw new MethodNotAllowedException(
                    "Method {$request->getMethod()} not allowed for {$request->getPath()}",
                    $routeInfo['allowed_methods']
                );
            }

            $route = $routeInfo['route'];
            $parameters = $routeInfo['parameters'];

            $this->recordRouteMatch($route, microtime(true) - $startTime);

            $this->eventDispatcher->dispatch(new RouteMatchedEvent($route, $request, $parameters));

            return $this->resolver->resolve($route, $request, $parameters);

        } catch (\Throwable $e) {
            $this->logger->error('Route dispatch failed', [
                'method' => $request->getMethod(),
                'path' => $request->getPath(),
                'error' => $e->getMessage(),
                'duration' => microtime(true) - $startTime
            ]);

            throw $e;
        }
    }

    public function resolve(Request $request): array
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        $cacheKey = $this->generateCacheKey($method, $path);

        if ($this->cachingEnabled && $cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $result = $this->resolver->resolveRoute($method, $path, $this->routes);

        if ($this->cachingEnabled) {
            $this->cache->put($cacheKey, $result, 3600);
        }

        return $result;
    }

    public function url(string $name, array $parameters = [], array $options = []): string
    {
        return $this->urlGenerator->route($name, $parameters, $options);
    }

    /**
     * Alias for url()
     *
     * @param string $name
     * @param array $parameters
     * @param array $options
     * @return string
     */
    public function route(string $name, array $parameters = [], array $options = []): string
    {
        return $this->url($name, $parameters, $options);
    }

    public function hasRoute(string $name): bool
    {
        return $this->routes->hasRoute($name);
    }

    public function getRoute(string $name): ?RouteInterface
    {
        return $this->routes->getByName($name);
    }

    public function getRoutes(array $filters = []): array
    {
        return $this->routes->all();
    }

    public function compile(array $options = []): bool
    {
        return $this->compiler->compile($this->routes, $options);
    }

    public function clearCache(): bool
    {
        return $this->cache->flush();
    }

    public function getMetrics(): array
    {
        return [
            'total_routes' => $this->routes->count(),
            'total_matches' => $this->totalRouteMatches,
            'total_time' => $this->totalMatchingTime,
            'usage_stats' => $this->routeUsageStats,
            'slow_routes' => $this->slowRoutes
        ];
    }

    public function setCaching(bool $enabled): self
    {
        $this->cachingEnabled = $enabled;
        return $this;
    }

    public function middleware(array $middleware): self
    {
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function namespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function domain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function pattern(string $name, string $pattern): self
    {
        $this->patterns[$name] = $pattern;
        return $this;
    }

    public function model(string $key, string $model, ?callable $resolver = null): self
    {
        $this->modelBindings[$key] = ['model' => $model, 'resolver' => $resolver];
        return $this;
    }

    protected function addRoute(array $methods, string $path, mixed $handler, array $options = []): RouteInterface
    {
        $attributes = array_merge($this->currentAttributes, $options);

        // Aplica o prefixo do grupo, se existir
        if (isset($attributes['prefix'])) {
            $path = '/' . trim($attributes['prefix'], '/') . '/' . ltrim($path, '/');
            // Normaliza barras duplas
            $path = preg_replace('#/+#', '/', $path);
        }

        $route = new Route($methods, $path, $handler, $attributes);

        $this->routes->add($route);

        $this->eventDispatcher->dispatch(new RouteRegisteredEvent($route));

        return $route;
    }

    protected function initializeParsers(): void
    {
        $this->yamlParser = new YamlRouteParser($this->config['parsers']['yaml'] ?? []);
        $this->phpParser = new PhpRouteParser($this->config['parsers']['php'] ?? []);
        $this->jsonParser = new JsonRouteParser($this->config['parsers']['json'] ?? []);

    }

    protected function initializeConfiguration(): void
    {
        $this->cachingEnabled = $this->config['caching']['enabled'] ?? true;
        $this->compilationEnabled = $this->config['compilation']['enabled'] ?? true;
    }

    protected function registerDefaultPatterns(): void
    {
        $this->patterns = [
            'id' => '[0-9]+',
            'slug' => '[a-zA-Z0-9\-_]+',
            'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        ];
    }

    protected function updateCurrentAttributes(): void
    {
        $this->currentAttributes = [];

        foreach ($this->groupStack as $group) {
            $this->currentAttributes = array_merge($this->currentAttributes, $group);
        }
    }

    protected function generateCacheKey(string $method, string $path): string
    {
        $tenantId = $this->tenantManager->current()?->getId() ?? 'default';
        return "route_cache:{$tenantId}:" . md5($method . ':' . $path);
    }

    protected function recordRouteMatch(RouteInterface $route, float $duration): void
    {
        $this->totalRouteMatches++;
        $this->totalMatchingTime += $duration;

        $routeName = $route->getName() ?? 'unnamed';

        if (!isset($this->routeUsageStats[$routeName])) {
            $this->routeUsageStats[$routeName] = [
                'hits' => 0,
                'total_time' => 0.0,
                'avg_time' => 0.0
            ];
        }

        $this->routeUsageStats[$routeName]['hits']++;
        $this->routeUsageStats[$routeName]['total_time'] += $duration;
        $this->routeUsageStats[$routeName]['avg_time'] =
            $this->routeUsageStats[$routeName]['total_time'] / $this->routeUsageStats[$routeName]['hits'];
    }
}