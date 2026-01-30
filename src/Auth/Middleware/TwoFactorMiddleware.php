<?php

namespace Ludelix\Auth\Middleware;

use Ludelix\Bridge\Bridge;

/**
 * TwoFactorMiddleware - Handles two-factor authentication verification
 * 
 * This middleware ensures that only users who have completed two-factor authentication
 * can access certain routes. Users who haven't verified 2FA are redirected to the 2FA page.
 * 
 * @package Ludelix\Auth\Middleware
 */
class TwoFactorMiddleware
{
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
        if (!$user || !method_exists($user, 'isTwoFactorVerified') || !$user->isTwoFactorVerified()) {
            header('Location: /2fa');
            exit;
        }
        return $next($request);
    }
}