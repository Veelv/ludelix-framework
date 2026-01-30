<?php

namespace Ludelix\PRT;

/**
 * Cookie Manager
 * 
 * Manages HTTP cookies with security features
 */
class Cookie
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
            'encrypt' => false,
            'prefix' => 'ludelix_'
        ], $config);
    }

    /**
     * Set cookie
     */
    public function set(string $name, string $value, int $expires = 0, array $options = []): bool
    {
        $options = array_merge($this->config, $options);
        $name = $options['prefix'] . $name;
        
        // Encrypt value if enabled
        if ($options['encrypt']) {
            $value = $this->encrypt($value);
        }
        
        return setcookie(
            $name,
            $value,
            $expires,
            $options['path'],
            $options['domain'],
            $options['secure'],
            $options['httponly']
        );
    }

    /**
     * Get cookie value
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $name = $this->config['prefix'] . $name;
        $value = $_COOKIE[$name] ?? $default;
        
        // Decrypt value if encrypted
        if ($value !== $default && $this->config['encrypt']) {
            $value = $this->decrypt($value);
        }
        
        return $value;
    }

    /**
     * Check if cookie exists
     */
    public function has(string $name): bool
    {
        $name = $this->config['prefix'] . $name;
        return isset($_COOKIE[$name]);
    }

    /**
     * Delete cookie
     */
    public function delete(string $name): bool
    {
        $name = $this->config['prefix'] . $name;
        return setcookie(
            $name,
            '',
            time() - 3600,
            $this->config['path'],
            $this->config['domain'],
            $this->config['secure'],
            $this->config['httponly']
        );
    }

    /**
     * Set cookie that expires when browser closes
     */
    public function session(string $name, string $value, array $options = []): bool
    {
        return $this->set($name, $value, 0, $options);
    }

    /**
     * Set cookie that lasts forever (well, 10 years)
     */
    public function forever(string $name, string $value, array $options = []): bool
    {
        return $this->set($name, $value, time() + (10 * 365 * 24 * 60 * 60), $options);
    }

    /**
     * Set cookie for specific duration
     */
    public function remember(string $name, string $value, int $minutes, array $options = []): bool
    {
        return $this->set($name, $value, time() + ($minutes * 60), $options);
    }

    /**
     * Get all cookies with prefix
     */
    public function all(): array
    {
        $cookies = [];
        $prefixLength = strlen($this->config['prefix']);
        
        foreach ($_COOKIE as $name => $value) {
            if (str_starts_with($name, $this->config['prefix'])) {
                $cleanName = substr($name, $prefixLength);
                $cookies[$cleanName] = $this->config['encrypt'] ? $this->decrypt($value) : $value;
            }
        }
        
        return $cookies;
    }

    /**
     * Clear all cookies with prefix
     */
    public function clear(): void
    {
        $prefixLength = strlen($this->config['prefix']);
        
        foreach ($_COOKIE as $name => $value) {
            if (str_starts_with($name, $this->config['prefix'])) {
                $cleanName = substr($name, $prefixLength);
                $this->delete($cleanName);
            }
        }
    }

    /**
     * Simple encryption (in production, use proper encryption)
     */
    protected function encrypt(string $value): string
    {
        return base64_encode($value);
    }

    /**
     * Simple decryption
     */
    protected function decrypt(string $value): string
    {
        return base64_decode($value) ?: $value;
    }

    /**
     * Create secure cookie for authentication
     */
    public function secure(string $name, string $value, int $expires = 0): bool
    {
        return $this->set($name, $value, $expires, [
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
            'encrypt' => true
        ]);
    }
}