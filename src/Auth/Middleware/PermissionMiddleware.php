<?php

namespace Ludelix\Auth\Middleware;

use Ludelix\Bridge\Bridge;

/**
 * PermissionMiddleware - Handles permission-based access control
 * 
 * This middleware ensures that only users with specific permissions can access certain routes.
 * Users without the required permission receive a 403 Forbidden response.
 * 
 * @package Ludelix\Auth\Middleware
 */
class PermissionMiddleware
{
    /**
     * PermissionMiddleware constructor.
     *
     * @param string $permission The required permission
     */
    public function __construct(protected string $permission) {}

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
        
        if (!$user || !method_exists($user, 'hasPermission') || !$user->hasPermission($this->permission)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        
        return $next($request);
    }
}