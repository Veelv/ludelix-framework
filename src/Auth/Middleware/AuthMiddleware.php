<?php

namespace Ludelix\Auth\Middleware;

use Ludelix\Bridge\Bridge;

/**
 * AuthMiddleware - Handles authentication for web routes
 * 
 * This middleware ensures that only authenticated users can access protected routes.
 * Unauthenticated users are redirected to the login page or receive a 401 response
 * for AJAX requests.
 * 
 * @package Ludelix\Auth\Middleware
 */
class AuthMiddleware
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
            if ($request->expectsJson()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            header('Location: /login');
            exit;
        }
        return $next($request);
    }
}