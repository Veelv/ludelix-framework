<?php

namespace Ludelix\ApiExplorer\Core;

use Ludelix\Routing\Core\Router;
use Ludelix\Routing\Core\Route;
use ReflectionClass;
use ReflectionMethod;
use Ludelix\ApiExplorer\Attributes\ApiEndpoint;
use Ludelix\ApiExplorer\Attributes\QueryParam;
use Ludelix\ApiExplorer\Attributes\BodyParam;
use Ludelix\ApiExplorer\Attributes\ApiResponse;

/**
 * Scans registered routes to generate API documentation.
 *
 * Iterates through the framework's routes, inspecting handlers via Reflection
 * to extract metadata defined by Attributes (ApiEndpoint, QueryParam, etc.).
 */
class Scanner
{
    protected Router $router;

    /**
     * @param Router $router The router instance to scan.
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Executes the scan and returns organized documentation.
     *
     * @return array Grouped API documentation [Tag => [EndpointData]].
     */
    public function scan(): array
    {
        $routes = $this->router->getRoutes();
        $docs = [];

        foreach ($routes as $route) {
            /** @var Route $route */
            $handler = $route->getHandler();

            // We only document Controller@method style handlers for now
            if (is_string($handler) && str_contains($handler, '@')) {
                [$controller, $method] = explode('@', $handler);

                if (class_exists($controller) && method_exists($controller, $method)) {
                    $doc = $this->analyzeEndpoint($route, $controller, $method);
                    if ($doc) {
                        $docs[] = $doc;
                    }
                }
            }
        }

        return $this->organizeDocs($docs);
    }

    /**
     * Analyzes a specific endpoint by reading its reflection metadata.
     *
     * @param Route  $route      The route being analyzed.
     * @param string $controller The fully qualified controller class name.
     * @param string $method     The controller method name.
     * @return array|null The endpoint data or null if it should be skipped.
     */
    protected function analyzeEndpoint(Route $route, string $controller, string $method): ?array
    {
        $reflection = new ReflectionMethod($controller, $method);

        // Find ApiEndpoint attribute
        $endpointAttr = $reflection->getAttributes(ApiEndpoint::class);

        if (empty($endpointAttr)) {
            // Optional: Skip routes without documentation attribute?
            // For now, let's include them with basic info if they are in 'api' prefix or similar
            // Or just return null to only document annotated routes.
            // Let's document everything but with default values.
            $summary = $route->getName() ?? 'Endpoint';
            $description = '';
            $tags = [$this->getSimpleControllerName($controller)];
        } else {
            $instance = $endpointAttr[0]->newInstance();
            $summary = $instance->summary;
            $description = $instance->description;
            $tags = !empty($instance->tags) ? $instance->tags : [$this->getSimpleControllerName($controller)];
        }

        return [
            'method' => $route->getMethods()[0], // Main method
            'path' => $route->getPath(),
            'summary' => $summary,
            'description' => $description,
            'tags' => $tags,
            'queryParams' => $this->getAttributes($reflection, QueryParam::class),
            'bodyParams' => $this->getAttributes($reflection, BodyParam::class),
            'responses' => $this->getAttributes($reflection, ApiResponse::class),
        ];
    }

    /**
     * Extracts instances of a specific attribute from a reflection method.
     *
     * @param ReflectionMethod $method         The method to inspect.
     * @param string           $attributeClass The attribute class name.
     * @return array List of attribute data.
     */
    protected function getAttributes(ReflectionMethod $method, string $attributeClass): array
    {
        $attributes = $method->getAttributes($attributeClass);
        $results = [];

        foreach ($attributes as $attr) {
            $instance = $attr->newInstance();
            $results[] = (array) $instance;
        }

        return $results;
    }

    /**
     * Extracts a simple name for a controller for tagging purposes.
     *
     * @param string $controller The fully qualified controller class name.
     * @return string The simple class name without 'Controller' suffix.
     */
    protected function getSimpleControllerName(string $controller): string
    {
        $parts = explode('\\', $controller);
        return str_replace('Controller', '', end($parts));
    }

    /**
     * Organizes raw endpoint data into a grouped structure by tags.
     *
     * @param array $docs Raw endpoint documentation list.
     * @return array Grouped documentation.
     */
    protected function organizeDocs(array $docs): array
    {
        $organized = [];
        foreach ($docs as $doc) {
            $tag = $doc['tags'][0] ?? 'General';
            $organized[$tag][] = $doc;
        }
        return $organized;
    }
}
