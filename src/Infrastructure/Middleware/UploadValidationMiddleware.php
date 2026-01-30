<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Middleware;

class UploadValidationMiddleware
{
    public function handle($request, $next)
    {
        // Placeholder for upload validation logic
        // e.g., checking file type, size, etc.

        return $next($request);
    }
}
