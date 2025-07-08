<?php

namespace Ludelix\Core\Security;

/**
 * Rate Limiter
 * 
 * Limits request rates to prevent abuse
 */
class RateLimiter
{
    protected array $attempts = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'prefix' => 'rate_limit'
        ], $config);
    }

    /**
     * Check if key is rate limited
     */
    public function tooManyAttempts(string $key, int $maxAttempts = null): bool
    {
        $maxAttempts = $maxAttempts ?? $this->config['max_attempts'];
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * Increment attempts for key
     */
    public function hit(string $key, int $decayMinutes = null): int
    {
        $decayMinutes = $decayMinutes ?? $this->config['decay_minutes'];
        $key = $this->resolveKey($key);
        
        $this->resetIfExpired($key, $decayMinutes);
        
        if (!isset($this->attempts[$key])) {
            $this->attempts[$key] = [
                'count' => 0,
                'reset_time' => time() + ($decayMinutes * 60)
            ];
        }
        
        $this->attempts[$key]['count']++;
        
        return $this->attempts[$key]['count'];
    }

    /**
     * Get attempts count for key
     */
    public function attempts(string $key): int
    {
        $key = $this->resolveKey($key);
        $this->resetIfExpired($key);
        
        return $this->attempts[$key]['count'] ?? 0;
    }

    /**
     * Reset attempts for key
     */
    public function resetAttempts(string $key): void
    {
        $key = $this->resolveKey($key);
        unset($this->attempts[$key]);
    }

    /**
     * Get seconds until reset
     */
    public function availableIn(string $key): int
    {
        $key = $this->resolveKey($key);
        
        if (!isset($this->attempts[$key])) {
            return 0;
        }
        
        return max(0, $this->attempts[$key]['reset_time'] - time());
    }

    /**
     * Clear all rate limits
     */
    public function clear(): void
    {
        $this->attempts = [];
    }

    /**
     * Rate limit for IP address
     */
    public function forIp(string $ip, int $maxAttempts = null, int $decayMinutes = null): bool
    {
        return $this->tooManyAttempts("ip:{$ip}", $maxAttempts);
    }

    /**
     * Rate limit for user
     */
    public function forUser(int $userId, int $maxAttempts = null, int $decayMinutes = null): bool
    {
        return $this->tooManyAttempts("user:{$userId}", $maxAttempts);
    }

    /**
     * Rate limit for route
     */
    public function forRoute(string $route, string $identifier, int $maxAttempts = null): bool
    {
        return $this->tooManyAttempts("route:{$route}:{$identifier}", $maxAttempts);
    }

    /**
     * Hit rate limit for IP
     */
    public function hitIp(string $ip, int $decayMinutes = null): int
    {
        return $this->hit("ip:{$ip}", $decayMinutes);
    }

    /**
     * Hit rate limit for user
     */
    public function hitUser(int $userId, int $decayMinutes = null): int
    {
        return $this->hit("user:{$userId}", $decayMinutes);
    }

    /**
     * Hit rate limit for route
     */
    public function hitRoute(string $route, string $identifier, int $decayMinutes = null): int
    {
        return $this->hit("route:{$route}:{$identifier}", $decayMinutes);
    }

    /**
     * Resolve rate limit key
     */
    protected function resolveKey(string $key): string
    {
        return $this->config['prefix'] . ':' . $key;
    }

    /**
     * Reset if expired
     */
    protected function resetIfExpired(string $key, int $decayMinutes = null): void
    {
        if (!isset($this->attempts[$key])) {
            return;
        }
        
        if (time() >= $this->attempts[$key]['reset_time']) {
            unset($this->attempts[$key]);
        }
    }

    /**
     * Get rate limit headers
     */
    public function getHeaders(string $key, int $maxAttempts = null): array
    {
        $maxAttempts = $maxAttempts ?? $this->config['max_attempts'];
        $attempts = $this->attempts($key);
        $remaining = max(0, $maxAttempts - $attempts);
        $resetTime = time() + $this->availableIn($key);
        
        return [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $resetTime
        ];
    }
}