<?php

namespace Ludelix\Core\Middleware;

use Ludelix\Security\CsrfManager;
use Ludelix\Security\Exceptions\TokenMismatchException;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * CSRF Protection Middleware
 * 
 * Validates CSRF tokens for state-changing requests (POST, PUT, PATCH, DELETE).
 * Throws TokenMismatchException (419) when validation fails.
 */
class CsrfMiddleware
{
    /**
     * @var CsrfManager
     */
    protected $csrfManager;

    /**
     * URIs that should be excluded from CSRF verification
     *
     * @var array
     */
    protected array $except = [];

    public function __construct(CsrfManager $csrfManager, array $except = [])
    {
        $this->csrfManager = $csrfManager;
        $this->except = $except;
    }

    /**
     * Handle the request and validate CSRF token
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws TokenMismatchException
     */
    public function handle(Request $request, callable $next): Response
    {
        // Skip validation for "safe" HTTP methods
        if ($this->isSafeMethod($request)) {
            return $next($request);
        }

        // Skip validation for excluded URIs
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        // Get token from request (form field or header)
        $token = $this->getTokenFromRequest($request);

        // Validate token - throw exception if invalid
        if (!$this->csrfManager->validate($token)) {
            throw new TokenMismatchException(
                'CSRF token validation failed. Please refresh the page and try again.'
            );
        }

        return $next($request);
    }

    /**
     * Check if the HTTP method is safe (doesn't modify state)
     *
     * @param Request $request
     * @return bool
     */
    protected function isSafeMethod(Request $request): bool
    {
        return in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS']);
    }

    /**
     * Check if the request URI is in the exception array
     *
     * @param Request $request
     * @return bool
     */
    protected function inExceptArray(Request $request): bool
    {
        $path = $request->getPath();

        foreach ($this->except as $pattern) {
            // Support wildcards
            if ($pattern === $path || fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get CSRF token from request
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Try form field first
        $token = $request->input(CsrfManager::getFormKey());

        // Fallback to header
        if (!$token) {
            $token = $request->getHeader('X-CSRF-TOKEN');
        }

        // Fallback to X-XSRF-TOKEN (used by some frameworks)
        if (!$token) {
            $token = $request->getHeader('X-XSRF-TOKEN');
        }

        return $token;
    }

    /**
     * Add URIs to exception list
     *
     * @param array $uris
     * @return void
     */
    public function except(array $uris): void
    {
        $this->except = array_merge($this->except, $uris);
    }
}
