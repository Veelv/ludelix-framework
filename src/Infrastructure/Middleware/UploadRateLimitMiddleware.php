<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Middleware;

class UploadRateLimitMiddleware
{
    public function handle($request, $next)
    {
        // Placeholder for rate limiting logic
        // e.g., checking uploads per minute per user/IP

        return $next($request);
    }
}
