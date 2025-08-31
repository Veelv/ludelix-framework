<?php

namespace Ludelix\Auth\Middleware;

use Ludelix\Bridge\Bridge;

/**
 * RoleMiddleware - Handles role-based access control
 * 
 * This middleware ensures that only users with specific roles can access certain routes.
 * Users without the required role receive a 403 Forbidden response.
 * 
 * @package Ludelix\Auth\Middleware
 */
class RoleMiddleware
{
    /**
     * RoleMiddleware constructor.
     *
     * @param string $role The required role
     */
    public function __construct(protected string $role) {}

    /**
     * Handle an incoming request
     *
     * @param mixed $request The incoming request
     * @param callable $next The next middleware callable
     * @return mixed
     */
    public function handle($request, $next)
    {
        $auth = Bridge::auth();
        $user = $auth->user();
        if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole($this->role)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        return $next($request);
    }
}