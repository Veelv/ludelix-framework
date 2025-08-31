<?php

namespace Ludelix\Connect\Middleware;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
use Ludelix\Connect\Connect;

/**
 * Connect Middleware
 * 
 * Handles Connect-specific request processing
 */
class ConnectMiddleware
{
    /**
     * Handle Connect requests
     */
    public function handle(Request $request, callable $next): Response
    {
        // Add Connect headers for identification
        if ($this->isConnectRequest($request)) {
            $request->headers->set('X-Ludelix-Connect', 'true');
        }

        $response = $next($request);

        // Add Connect-specific headers to response
        if ($this->isConnectRequest($request)) {
            $response->headers->set('Vary', 'X-Ludelix-Connect');
            $response->headers->set('X-Ludelix-Version', Connect::getInstance()->getVersion());
        }

        return $response;
    }

    /**
     * Check if request is from ludelix-connect
     */
    protected function isConnectRequest(Request $request): bool
    {
        return $request->hasHeader('X-Ludelix-Connect') ||
               $request->hasHeader('X-Requested-With') && 
               $request->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }
}