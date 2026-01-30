<?php

namespace Ludelix\PRT;

use Ludelix\Interface\DI\ContainerInterface;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
use Ludelix\Routing\Exceptions\RouteNotFoundException;
use Ludelix\Routing\Exceptions\MethodNotAllowedException;

class RequestHandler
{
    protected ContainerInterface $container;
    protected array $middleware = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(): Response
    {
        try {
            $request = new Request();
            $this->container->instance('request', $request);

            // Process middleware pipeline
            $response = $this->processMiddleware($request);
            
            // Dispatch route if no middleware returned response
            if (!$response) {
                $response = $this->dispatchRoute($request);
            }

            return $response instanceof Response ? $response : new Response($response);

        } catch (\Throwable $e) {
            return $this->handleException($e, $request ?? new Request());
        }
    }

    protected function processMiddleware(Request $request): ?Response
    {
        foreach ($this->middleware as $middleware) {
            $result = $this->container->call($middleware, ['request' => $request]);
            if ($result instanceof Response) {
                return $result;
            }
        }
        return null;
    }

    protected function dispatchRoute(Request $request): Response
    {
        if ($this->container->has('router')) {
            $router = $this->container->get('router');
            return $router->dispatch($request);
        }

        // Fallback response
        return Response::json([
            'framework' => 'Ludelix Framework',
            'version' => $this->container->get('app')->version(),
            'status' => 'running',
            'message' => 'No routes defined'
        ]);
    }

    protected function handleException(\Throwable $e, Request $request): Response
    {
        $debug = $this->container->get('app')->isDebug();
        $wantsJson = $this->wantsJson($request);
        
        // Handle specific exceptions
        if ($e instanceof RouteNotFoundException) {
            return $this->renderError(404, 'Página não encontrada', $e->getMessage(), $wantsJson, $debug);
        }
        
        if ($e instanceof MethodNotAllowedException) {
            return $this->renderError(405, 'Método não permitido', $e->getMessage(), $wantsJson, $debug);
        }
        
        // Handle other exceptions
        return $this->renderError(500, 'Erro interno do servidor', $e->getMessage(), $wantsJson, $debug);
    }

    protected function renderError(int $statusCode, string $title, string $message, bool $wantsJson, bool $debug): Response
    {
        if ($wantsJson) {
            return Response::json([
                'error' => $title,
                'message' => $message,
                'status' => $statusCode
            ], $statusCode);
        }

        try {
            // Try to render error template
            if ($this->container->has('ludou')) {
                $ludou = $this->container->get('ludou');
                $template = "errors.{$statusCode}";
                
                if ($ludou->exists($template)) {
                    $content = $ludou->render($template, [
                        'title' => $title,
                        'message' => $message,
                        'status' => $statusCode,
                        'debug' => $debug
                    ]);
                    
                    return new Response($content, $statusCode, ['Content-Type' => 'text/html; charset=UTF-8']);
                }
            }
        } catch (\Throwable $templateError) {
            // Fallback if template rendering fails
        }

        // Fallback to basic HTML error page
        return $this->renderBasicError($statusCode, $title, $message, $debug);
    }

    protected function renderBasicError(int $statusCode, string $title, string $message, bool $debug): Response
    {
        $debugInfo = '';
        if ($debug) {
            $debugInfo = '<div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px; font-family: monospace; font-size: 12px;">';
            $debugInfo .= '<h3>Debug Information</h3>';
            $debugInfo .= '<p><strong>Status:</strong> ' . $statusCode . '</p>';
            $debugInfo .= '<p><strong>Message:</strong> ' . htmlspecialchars($message) . '</p>';
            $debugInfo .= '</div>';
        }

        $html = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: #f8f9fa; 
            color: #333; 
        }
        .error-container { 
            max-width: 600px; 
            margin: 100px auto; 
            padding: 40px; 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
        }
        .error-code { 
            font-size: 72px; 
            font-weight: bold; 
            color: #dc3545; 
            margin: 0; 
            text-align: center; 
        }
        .error-title { 
            font-size: 24px; 
            color: #495057; 
            margin: 20px 0; 
            text-align: center; 
        }
        .error-message { 
            font-size: 16px; 
            color: #6c757d; 
            text-align: center; 
            margin: 20px 0; 
            line-height: 1.6; 
        }
        .back-link { 
            text-align: center; 
            margin-top: 30px; 
        }
        .back-link a { 
            color: #007bff; 
            text-decoration: none; 
        }
        .back-link a:hover { 
            text-decoration: underline; 
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">' . $statusCode . '</h1>
        <h2 class="error-title">' . htmlspecialchars($title) . '</h2>
        <p class="error-message">' . htmlspecialchars($message) . '</p>
        <div class="back-link">
            <a href="/">← Voltar ao Início</a>
        </div>
        ' . $debugInfo . '
    </div>
</body>
</html>';

        return new Response($html, $statusCode, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    protected function wantsJson(Request $request): bool
    {
        $accept = $request->getHeader('accept') ?? '';
        return str_contains($accept, 'application/json') || 
               str_contains($accept, 'text/json') ||
               $request->getPath() === '/api' ||
               str_starts_with($request->getPath(), '/api/');
    }

    public function addMiddleware(string $middleware): void
    {
        $this->middleware[] = $middleware;
    }
}