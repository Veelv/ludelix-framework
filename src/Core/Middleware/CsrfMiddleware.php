<?php

namespace Ludelix\Core\Middleware;

use Ludelix\Security\CsrfManager;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

class CsrfMiddleware
{
    /**
     * @var CsrfManager
     */
    protected $csrfManager;

    public function __construct(CsrfManager $csrfManager)
    {
        $this->csrfManager = $csrfManager;
    }

    /**
     * Lida com a requisição e valida o token CSRF.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        // Pula a validação para métodos "seguros"
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        $token = $request->input(CsrfManager::getFormKey()) ?? $request->header('X-CSRF-TOKEN');

        if (!$this->csrfManager->validate($token)) {
            // Em um app real, você poderia lançar uma exceção aqui
            // que seria convertida para uma resposta 419.
            return new Response('CSRF token mismatch.', 419);
        }

        return $next($request);
    }
} 