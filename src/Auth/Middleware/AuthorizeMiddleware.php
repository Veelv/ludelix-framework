<?php

namespace Ludelix\Auth\Middleware;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
use Ludelix\Auth\Attributes\Authorize;
use Ludelix\Bridge\Bridge;
use ReflectionMethod;

/**
 * AuthorizeMiddleware - Automatic authorization based on #[Authorize] attribute.
 */
class AuthorizeMiddleware
{
    /**
     * Handle the request and check for #[Authorize] attribute.
     */
    public function handle(Request $request, callable $next): Response
    {
        $handler = $request->getAttribute('_handler');

        if ($handler && is_string($handler) && str_contains($handler, '@')) {
            [$controller, $method] = explode('@', $handler, 2);

            if (class_exists($controller) && method_exists($controller, $method)) {
                $reflection = new ReflectionMethod($controller, $method);
                $attributes = $reflection->getAttributes(Authorize::class);

                if (!empty($attributes)) {
                    // Use JWT guard for API authorization
                    $auth = Bridge::auth();

                    // We might need to ensure the guard is set to JWT
                    // This depends on how AuthManager is configured.
                    if (!$auth->check()) {
                        return new Response(json_encode([
                            'success' => false,
                            'message' => 'Unauthorized. Valid JWT token required.'
                        ]), 401, ['Content-Type' => 'application/json']);
                    }
                }
            }
        }

        return $next($request);
    }
}
