<?php

namespace Ludelix\Security\RateLimiting;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * Rate Limit Middleware
 * 
 * Aplica rate limiting automaticamente nas requisições
 */
class RateLimitMiddleware
{
    private RateLimiter $rateLimiter;
    private array $config;

    public function __construct(RateLimiter $rateLimiter, array $config = [])
    {
        $this->rateLimiter = $rateLimiter;
        $this->config = array_merge([
            'default_attempts' => 60,
            'default_decay_minutes' => 1,
            'exempt_routes' => [],
            'exempt_ips' => [],
            'response_headers' => true,
        ], $config);
    }

    /**
     * Handle request
     */
    public function handle(Request $request, callable $next): Response
    {
        // Verificar se a rota está isenta
        if ($this->isExempt($request)) {
            return $next($request);
        }

        // Gerar chave de rate limiting
        $key = $this->generateKey($request);
        
        // Verificar se está bloqueado
        if ($this->rateLimiter->isBlocked($key)) {
            return $this->tooManyRequestsResponse($key);
        }

        // Verificar rate limit
        if (!$this->rateLimiter->attempt($key)) {
            // Bloquear temporariamente
            $this->rateLimiter->block($key);
            return $this->tooManyRequestsResponse($key);
        }

        // Processar requisição
        $response = $next($request);

        // Adicionar headers de rate limiting
        if ($this->config['response_headers']) {
            $this->addRateLimitHeaders($response, $key);
        }

        return $response;
    }

    /**
     * Verifica se a requisição está isenta
     */
    private function isExempt(Request $request): bool
    {
        $path = $request->getPath();
        $ip = $request->getClientIp();

        // Verificar rotas isentas
        foreach ($this->config['exempt_routes'] as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        // Verificar IPs isentos
        if (in_array($ip, $this->config['exempt_ips'])) {
            return true;
        }

        return false;
    }

    /**
     * Gera chave de rate limiting
     */
    private function generateKey(Request $request): string
    {
        $ip = $request->getClientIp();
        $userAgent = $request->getUserAgent();
        $path = $request->getPath();
        
        // Usar IP + User-Agent + Path para maior precisão
        return hash('sha256', "{$ip}:{$userAgent}:{$path}");
    }

    /**
     * Resposta para muitas requisições
     */
    private function tooManyRequestsResponse(string $key): Response
    {
        $retryAfter = $this->rateLimiter->availableIn($key);
        
        return new Response(
            'Too Many Requests',
            429,
            [
                'Content-Type' => 'text/plain',
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $this->config['default_attempts'],
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => time() + $retryAfter,
            ]
        );
    }

    /**
     * Adiciona headers de rate limiting
     */
    private function addRateLimitHeaders(Response $response, string $key): void
    {
        $remaining = $this->rateLimiter->remaining($key);
        $availableIn = $this->rateLimiter->availableIn($key);
        
        $response->headers([
            'X-RateLimit-Limit' => $this->config['default_attempts'],
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => time() + $availableIn,
        ]);
    }

    /**
     * Configura rotas isentas
     */
    public function exempt(array $routes): self
    {
        $this->config['exempt_routes'] = array_merge(
            $this->config['exempt_routes'],
            $routes
        );
        return $this;
    }

    /**
     * Configura IPs isentos
     */
    public function exemptIPs(array $ips): self
    {
        $this->config['exempt_ips'] = array_merge(
            $this->config['exempt_ips'],
            $ips
        );
        return $this;
    }
} 