<?php

namespace Ludelix\Security\RateLimiting;

use Ludelix\Infrastructure\Contracts\CacheInterface;

/**
 * Rate Limiter
 * 
 * Protege contra ataques de força bruta e DDoS
 */
class RateLimiter
{
    private CacheInterface $cache;
    private array $config;

    public function __construct(CacheInterface $cache, array $config = [])
    {
        $this->cache = $cache;
        $this->config = array_merge([
            'default_attempts' => 60,
            'default_decay_minutes' => 1,
            'max_attempts' => 100,
            'max_decay_minutes' => 60,
            'block_duration' => 900, // 15 minutos
        ], $config);
    }

    /**
     * Verifica se a requisição pode prosseguir
     */
    public function attempt(string $key, int $maxAttempts = null, int $decayMinutes = null): bool
    {
        $maxAttempts = $maxAttempts ?? $this->config['default_attempts'];
        $decayMinutes = $decayMinutes ?? $this->config['default_decay_minutes'];
        
        $key = $this->getCacheKey($key);
        $hits = $this->cache->get($key, 0);
        
        if ($hits >= $maxAttempts) {
            return false;
        }
        
        $this->cache->put($key, $hits + 1, $decayMinutes * 60);
        return true;
    }

    /**
     * Obtém o número de tentativas restantes
     */
    public function remaining(string $key, int $maxAttempts = null): int
    {
        $maxAttempts = $maxAttempts ?? $this->config['default_attempts'];
        $key = $this->getCacheKey($key);
        $hits = $this->cache->get($key, 0);
        
        return max(0, $maxAttempts - $hits);
    }

    /**
     * Obtém o tempo até o reset
     */
    public function availableIn(string $key, int $decayMinutes = null): int
    {
        $decayMinutes = $decayMinutes ?? $this->config['default_decay_minutes'];
        $key = $this->getCacheKey($key);
        
        return $this->cache->getTimeToLive($key) ?? 0;
    }

    /**
     * Limpa as tentativas para uma chave
     */
    public function clear(string $key): bool
    {
        $key = $this->getCacheKey($key);
        return $this->cache->forget($key);
    }

    /**
     * Bloqueia uma chave por um período
     */
    public function block(string $key, int $minutes = null): bool
    {
        $minutes = $minutes ?? ($this->config['block_duration'] / 60);
        $key = $this->getCacheKey($key, 'block');
        
        return $this->cache->put($key, time(), $minutes * 60);
    }

    /**
     * Verifica se uma chave está bloqueada
     */
    public function isBlocked(string $key): bool
    {
        $key = $this->getCacheKey($key, 'block');
        return $this->cache->has($key);
    }

    /**
     * Desbloqueia uma chave
     */
    public function unblock(string $key): bool
    {
        $key = $this->getCacheKey($key, 'block');
        return $this->cache->forget($key);
    }

    /**
     * Gera chave de cache
     */
    private function getCacheKey(string $key, string $suffix = ''): string
    {
        $prefix = 'rate_limit';
        $suffix = $suffix ? "_{$suffix}" : '';
        return "{$prefix}:{$key}{$suffix}";
    }

    /**
     * Obtém estatísticas de rate limiting
     */
    public function getStats(string $key): array
    {
        $attemptsKey = $this->getCacheKey($key);
        $blockKey = $this->getCacheKey($key, 'block');
        
        return [
            'attempts' => $this->cache->get($attemptsKey, 0),
            'remaining' => $this->remaining($key),
            'available_in' => $this->availableIn($key),
            'is_blocked' => $this->isBlocked($key),
            'block_until' => $this->cache->get($blockKey),
        ];
    }
} 