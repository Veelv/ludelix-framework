<?php

namespace Ludelix\Auth\Middleware;

use Ludelix\Bridge\Bridge;

/**
 * ApiAuthMiddleware - Handles API authentication
 * 
 * This middleware ensures that only authenticated users can access API routes.
 * It returns a 401 Unauthorized response for unauthenticated requests.
 * 
 * @package Ludelix\Auth\Middleware
 */
class ApiAuthMiddleware
{
    /**
     * Handle an incoming request
     *
     * @param mixed $request The incoming request
     * @param callable $next The next middleware callable
     * @return mixed
     */
    public function handle(
        $request,
        $next
    ) {
        $auth = Bridge::auth();
        if (!$auth->check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        return $next($request);
    }
}