<?php

namespace Ludelix\Routing\Middleware;

use Ludelix\Interface\Routing\MiddlewareInterface;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * CORS Middleware - Cross-Origin Resource Sharing
 * 
 * Handles CORS (Cross-Origin Resource Sharing) for API routes.
 * Automatically handles preflight OPTIONS requests and adds appropriate
 * CORS headers to responses.
 * 
 * @package Ludelix\Routing\Middleware
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class CorsMiddleware implements MiddlewareInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
            'exposed_headers' => [],
            'max_age' => 86400, // 24 hours
            'supports_credentials' => false,
        ], $config);
    }

    /**
     * Handle CORS for incoming request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest($request);
        }

        // Process the request
        $response = $next($request);

        // Add CORS headers to response
        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Handle preflight OPTIONS request
     * 
     * @param Request $request
     * @return Response
     */
    protected function handlePreflightRequest(Request $request): Response
    {
        $response = new Response('', 204);

        $origin = $request->getHeader('Origin') ?? '';

        if ($this->isOriginAllowed($origin)) {
            $response->setHeader('Access-Control-Allow-Origin', $this->getAllowedOrigin($origin));
            $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods']));
            $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers']));
            $response->setHeader('Access-Control-Max-Age', (string) $this->config['max_age']);

            if ($this->config['supports_credentials']) {
                $response->setHeader('Access-Control-Allow-Credentials', 'true');
            }
        }

        return $response;
    }

    /**
     * Add CORS headers to response
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function addCorsHeaders(Request $request, Response $response): Response
    {
        $origin = $request->getHeader('Origin') ?? '';

        if (!$this->isOriginAllowed($origin)) {
            return $response;
        }

        $response->setHeader('Access-Control-Allow-Origin', $this->getAllowedOrigin($origin));

        if ($this->config['supports_credentials']) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($this->config['exposed_headers'])) {
            $response->setHeader('Access-Control-Expose-Headers', implode(', ', $this->config['exposed_headers']));
        }

        // Add Vary header to indicate that response varies based on Origin
        $vary = $response->getHeader('Vary');
        if ($vary) {
            $response->setHeader('Vary', $vary . ', Origin');
        } else {
            $response->setHeader('Vary', 'Origin');
        }

        return $response;
    }

    /**
     * Check if origin is allowed
     * 
     * @param string $origin
     * @return bool
     */
    protected function isOriginAllowed(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        // Allow all origins
        if (in_array('*', $this->config['allowed_origins'])) {
            return true;
        }

        // Check exact match
        if (in_array($origin, $this->config['allowed_origins'])) {
            return true;
        }

        // Check pattern match (e.g., *.example.com)
        foreach ($this->config['allowed_origins'] as $allowedOrigin) {
            if ($this->matchesPattern($origin, $allowedOrigin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get allowed origin for response header
     * 
     * @param string $origin
     * @return string
     */
    protected function getAllowedOrigin(string $origin): string
    {
        // If credentials are supported, we must return the specific origin
        // (not '*') for security reasons
        if ($this->config['supports_credentials']) {
            return $origin;
        }

        // If all origins are allowed and credentials are not supported,
        // we can return '*'
        if (in_array('*', $this->config['allowed_origins'])) {
            return '*';
        }

        return $origin;
    }

    /**
     * Match origin against pattern
     * 
     * @param string $origin Origin to check
     * @param string $pattern Pattern to match against
     * @return bool
     */
    protected function matchesPattern(string $origin, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = str_replace(
            ['*', '.'],
            ['.*', '\\.'],
            $pattern
        );

        return (bool) preg_match('#^' . $regex . '$#', $origin);
    }

    /**
     * Configure allowed origins
     * 
     * @param array $origins
     * @return self
     */
    public function allowOrigins(array $origins): self
    {
        $this->config['allowed_origins'] = $origins;
        return $this;
    }

    /**
     * Configure allowed methods
     * 
     * @param array $methods
     * @return self
     */
    public function allowMethods(array $methods): self
    {
        $this->config['allowed_methods'] = $methods;
        return $this;
    }

    /**
     * Configure allowed headers
     * 
     * @param array $headers
     * @return self
     */
    public function allowHeaders(array $headers): self
    {
        $this->config['allowed_headers'] = $headers;
        return $this;
    }

    /**
     * Configure exposed headers
     * 
     * @param array $headers
     * @return self
     */
    public function exposeHeaders(array $headers): self
    {
        $this->config['exposed_headers'] = $headers;
        return $this;
    }

    /**
     * Enable credentials support
     * 
     * @param bool $supports
     * @return self
     */
    public function allowCredentials(bool $supports = true): self
    {
        $this->config['supports_credentials'] = $supports;
        return $this;
    }

    /**
     * Set max age for preflight cache
     * 
     * @param int $seconds
     * @return self
     */
    public function maxAge(int $seconds): self
    {
        $this->config['max_age'] = $seconds;
        return $this;
    }
}
