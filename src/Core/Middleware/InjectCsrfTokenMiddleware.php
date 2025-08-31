<?php

namespace Ludelix\Core\Middleware;

use Ludelix\Security\CsrfManager;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

class InjectCsrfTokenMiddleware
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
     * Injeta o token CSRF nos formulários da resposta HTML.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Só processa respostas HTML
        if (strpos($response->getHeaderLine('Content-Type'), 'text/html') === false) {
            return $response;
        }

        $content = $response->getBody()->getContents();

        $tokenInput = $this->csrfManager->generateInput();

        // Regex para encontrar forms com method POST (case-insensitive) e injetar o token
        $content = preg_replace_callback(
            '/(<form\b[^>]*\bmethod\s*=\s*["\'](POST|PUT|PATCH|DELETE)["\'][^>]*>)/i',
            function ($matches) use ($tokenInput) {
                // Adiciona o input do token logo após a tag de abertura do form
                return $matches[1] . "\n" . $tokenInput;
            },
            $content
        );

        // Retorna uma nova resposta com o conteúdo modificado
        return $response->withBody(new \Ludelix\PRT\Stream($content));
    }
} 