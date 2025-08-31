<?php

namespace Ludelix\PRT;

/**
 * Header Bag
 * 
 * Manages HTTP headers collection
 */
class HeaderBag
{
    protected array $headers = [];

    public function __construct(array $headers = [])
    {
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    /**
     * Set header
     */
    public function set(string $name, string $value): void
    {
        $this->headers[strtolower($name)] = $value;
    }

    /**
     * Get header value
     */
    public function get(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /**
     * Check if header exists
     */
    public function has(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * Remove header
     */
    public function remove(string $name): void
    {
        unset($this->headers[strtolower($name)]);
    }

    /**
     * Get all headers
     */
    public function all(): array
    {
        return $this->headers;
    }

    /**
     * Get header names
     */
    public function keys(): array
    {
        return array_keys($this->headers);
    }

    /**
     * Get header values
     */
    public function values(): array
    {
        return array_values($this->headers);
    }

    /**
     * Clear all headers
     */
    public function clear(): void
    {
        $this->headers = [];
    }

    /**
     * Count headers
     */
    public function count(): int
    {
        return count($this->headers);
    }

    /**
     * Check if empty
     */
    public function isEmpty(): bool
    {
        return empty($this->headers);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->headers;
    }

    /**
     * Get content type
     */
    public function getContentType(): ?string
    {
        return $this->get('content-type');
    }

    /**
     * Set content type
     */
    public function setContentType(string $contentType): void
    {
        $this->set('content-type', $contentType);
    }

    /**
     * Get authorization header
     */
    public function getAuthorization(): ?string
    {
        return $this->get('authorization');
    }

    /**
     * Set authorization header
     */
    public function setAuthorization(string $authorization): void
    {
        $this->set('authorization', $authorization);
    }

    /**
     * Get user agent
     */
    public function getUserAgent(): ?string
    {
        return $this->get('user-agent');
    }

    /**
     * Get accept header
     */
    public function getAccept(): ?string
    {
        return $this->get('accept');
    }

    /**
     * Check if accepts JSON
     */
    public function acceptsJson(): bool
    {
        $accept = $this->getAccept();
        return $accept && str_contains($accept, 'application/json');
    }

    /**
     * Check if accepts HTML
     */
    public function acceptsHtml(): bool
    {
        $accept = $this->getAccept();
        return $accept && str_contains($accept, 'text/html');
    }
}