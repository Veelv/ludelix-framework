<?php

namespace Ludelix\PRT;

/**
 * HTTP Response
 * 
 * Represents an HTTP response with status, headers and content
 */
class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected mixed $content = '';
    protected array $cookies = [];

    public function __construct(mixed $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = array_merge([
            'Content-Type' => 'text/html; charset=UTF-8'
        ], $headers);
    }

    /**
     * Set response content
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get response content
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Set status code
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get header
     */
    public function getHeader(string $name): ?string
    {
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
     * Set cookie
     */
    public function setCookie(string $name, string $value, array $options = []): self
    {
        $this->cookies[$name] = array_merge([
            'value' => $value,
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax'
        ], $options);
        
        return $this;
    }

    /**
     * Create JSON response
     */
    public static function json(array $data, int $statusCode = 200): self
    {
        return new self(json_encode($data), $statusCode, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Create redirect response
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, [
            'Location' => $url
        ]);
    }

    /**
     * Create file download response
     */
    public static function download(string $filePath, ?string $filename = null): self
    {
        if (!file_exists($filePath)) {
            return new self('File not found', 404);
        }

        $filename = $filename ?? basename($filePath);
        $content = file_get_contents($filePath);
        
        return new self($content, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string)strlen($content)
        ]);
    }

    /**
     * Create view response
     */
    public static function view(string $template, array $data = []): self
    {
        // This would integrate with the template engine
        $content = "<!-- Template: {$template} -->\n" . json_encode($data);
        return new self($content);
    }

    /**
     * Send response to browser
     */
    public function send(): void
    {
        // Send status code
        http_response_code($this->statusCode);
        
        // Send headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        // Send cookies
        foreach ($this->cookies as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                $cookie['expires'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }
        
        // Send content
        echo $this->content;
    }

    /**
     * Check if response is successful
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is redirect
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if response is client error
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is server error
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return (string)$this->content;
    }
}