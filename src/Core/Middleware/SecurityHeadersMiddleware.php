<?php

namespace Ludelix\Core\Middleware;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

class SecurityHeadersMiddleware
{
    /**
     * Adiciona headers de segurança à resposta.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Adiciona os headers
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // Você pode adicionar mais headers aqui, como Content-Security-Policy
        // $response->setHeader('Content-Security-Policy', "default-src 'self'");

        return $response;
    }
}