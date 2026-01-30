<?php

namespace Ludelix\Security\CSRF;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * CSRF Protection Middleware
 * 
 * Protects against Cross-Site Request Forgery attacks
 */
class CsrfMiddleware
{
    protected array $config;
    protected array $except = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'token_name' => '_token',
            'header_name' => 'X-CSRF-TOKEN',
            'session_key' => '_csrf_token',
            'regenerate_on_mismatch' => true
        ], $config);
    }

    /**
     * Handle request
     */
    public function handle(Request $request, callable $next): Response
    {
        // Skip CSRF for safe methods
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Skip CSRF for excepted routes
        if ($this->isExcepted($request)) {
            return $next($request);
        }

        // Verify CSRF token
        if (!$this->verifyToken($request)) {
            return $this->tokenMismatchResponse();
        }

        return $next($request);
    }

    /**
     * Verify CSRF token
     */
    protected function verifyToken(Request $request): bool
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = $this->getSessionToken();

        if (!$token || !$sessionToken) {
            return false;
        }

        $valid = hash_equals($sessionToken, $token);

        if (!$valid && $this->config['regenerate_on_mismatch']) {
            $this->regenerateToken();
        }

        return $valid;
    }

    /**
     * Get token from request
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Try POST data first
        $token = $request->post($this->config['token_name']);
        
        if (!$token) {
            // Try header
            $token = $request->getHeader($this->config['header_name']);
        }

        return $token;
    }

    /**
     * Get session token
     */
    protected function getSessionToken(): ?string
    {
        if (!isset($_SESSION)) {
            return null;
        }

        return $_SESSION[$this->config['session_key']] ?? null;
    }

    /**
     * Generate new CSRF token
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$this->config['session_key']] = $token;
        }

        return $token;
    }

    /**
     * Regenerate CSRF token
     */
    public function regenerateToken(): string
    {
        return $this->generateToken();
    }

    /**
     * Get current token
     */
    public function getToken(): string
    {
        $token = $this->getSessionToken();
        
        if (!$token) {
            $token = $this->generateToken();
        }

        return $token;
    }

    /**
     * Check if route is excepted
     */
    protected function isExcepted(Request $request): bool
    {
        $path = $request->getPath();
        
        foreach ($this->except as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add excepted routes
     */
    public function except(array $routes): self
    {
        $this->except = array_merge($this->except, $routes);
        return $this;
    }

    /**
     * Token mismatch response
     */
    protected function tokenMismatchResponse(): Response
    {
        return new Response('CSRF token mismatch', 419, [
            'Content-Type' => 'text/plain'
        ]);
    }

    /**
     * Generate HTML input field
     */
    public function field(): string
    {
        $token = $this->getToken();
        $name = $this->config['token_name'];
        
        return "<input type=\"hidden\" name=\"{$name}\" value=\"{$token}\">";
    }

    /**
     * Generate meta tag
     */
    public function metaTag(): string
    {
        $token = $this->getToken();
        
        return "<meta name=\"csrf-token\" content=\"{$token}\">";
    }
}