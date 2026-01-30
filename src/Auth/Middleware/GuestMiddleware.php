<?php

namespace Ludelix\Auth\Middleware;

use Ludelix\Bridge\Bridge;

/**
 * GuestMiddleware - Handles guest-only routes
 * 
 * This middleware ensures that only guests (unauthenticated users) can access certain routes.
 * Authenticated users are redirected to the dashboard.
 * 
 * @package Ludelix\Auth\Middleware
 */
class GuestMiddleware
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
        if ($auth->check()) {
            header('Location: /dashboard');
            exit;
        }
        return $next($request);
    }
}