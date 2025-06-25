<?php

namespace Ludelix\PRT;

/**
 * HTTP Request
 * 
 * Represents an HTTP request with all necessary data
 */
class Request
{
    protected string $method;
    protected string $uri;
    protected array $headers = [];
    protected array $query = [];
    protected array $post = [];
    protected array $files = [];
    protected array $cookies = [];
    protected array $server = [];
    protected ?string $body = null;
    protected array $attributes = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->headers = $this->parseHeaders();
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->server = $_SERVER;
        $this->body = file_get_contents('php://input');
    }

    /**
     * Get HTTP method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get path from URI
     */
    public function getPath(): string
    {
        return parse_url($this->uri, PHP_URL_PATH) ?? '/';
    }

    /**
     * Get header value
     */
    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get query parameter
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get POST parameter
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get input (query or post)
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get all input data
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Get JSON data
     */
    public function json(): ?array
    {
        if ($this->getHeader('content-type') === 'application/json') {
            return json_decode($this->body, true);
        }
        return null;
    }

    /**
     * Get uploaded file
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get cookie value
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get server variable
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get request body
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return $this->getHeader('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * Check if request is JSON
     */
    public function isJson(): bool
    {
        return str_contains($this->getHeader('content-type') ?? '', 'application/json');
    }

    /**
     * Check if request expects JSON
     */
    public function expectsJson(): bool
    {
        return str_contains($this->getHeader('accept') ?? '', 'application/json');
    }

    /**
     * Check if request is secure (HTTPS)
     */
    public function isSecure(): bool
    {
        return $this->server('HTTPS') === 'on' || $this->server('SERVER_PORT') == 443;
    }

    /**
     * Get client IP address
     */
    public function getClientIp(): string
    {
        return $this->getHeader('x-forwarded-for') 
            ?? $this->getHeader('x-real-ip')
            ?? $this->server('REMOTE_ADDR') 
            ?? '127.0.0.1';
    }

    /**
     * Set attribute
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get attribute
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Parse HTTP headers
     */
    protected function parseHeaders(): array
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        
        // Add content-type and content-length if present
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['content-type'] = $this->server['CONTENT_TYPE'];
        }
        
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['content-length'] = $this->server['CONTENT_LENGTH'];
        }
        
        return $headers;
    }
}