<?php

namespace Ludelix\Exceptions;

use Ludelix\Ludou\Core\TemplateEngine;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * Error Handler - Elegant Error Rendering
 * 
 * Handles exceptions and renders them in beautiful layouts
 * Similar to Laravel's error handling system
 */
class ErrorHandler
{
    protected TemplateEngine $templateEngine;
    protected bool $debug;
    protected string $environment;

    public function __construct(TemplateEngine $templateEngine, bool $debug = false, string $environment = 'production')
    {
        $this->templateEngine = $templateEngine;
        $this->debug = $debug;
        $this->environment = $environment;
    }

    /**
     * Handle an exception and return a response
     */
    public function handle(\Throwable $exception, Request $request = null): Response
    {
        $statusCode = $this->getStatusCode($exception);
        $message = $this->getMessage($exception);
        $title = $this->getTitle($exception);

        // Check if request expects JSON
        $wantsJson = $request && $this->wantsJson($request);

        if ($wantsJson) {
            return $this->renderJson($exception, $statusCode);
        }

        return $this->renderHtml($exception, $statusCode, $title, $message);
    }

    /**
     * Render JSON error response
     */
    protected function renderJson(\Throwable $exception, int $statusCode): Response
    {
        $data = [
            'error' => $this->getTitle($exception),
            'message' => $this->getMessage($exception),
            'status' => $statusCode
        ];

        if ($this->debug) {
            $data['file'] = $exception->getFile();
            $data['line'] = $exception->getLine();
            $data['trace'] = $exception->getTraceAsString();
        }

        return new Response(
            json_encode($data, JSON_PRETTY_PRINT),
            $statusCode,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Render HTML error response
     */
    protected function renderHtml(\Throwable $exception, int $statusCode, string $title, string $message): Response
    {
        $data = [
            'title' => $title,
            'message' => $message,
            'status' => $statusCode,
            'exception' => $exception,
            'debug' => $this->debug
        ];

        try {
            // Try to render error template directly
            $content = $this->templateEngine->render('errors.' . $statusCode, $data);
        } catch (\Exception $e) {
            // Fallback to basic error template
            $content = $this->renderBasicError($title, $message, $statusCode, $exception);
        }

        return new Response($content, $statusCode, ['Content-Type' => 'text/html']);
    }

    /**
     * Render basic error when templates are not available
     */
    protected function renderBasicError(string $title, string $message, int $statusCode, \Throwable $exception): string
    {
        $debugInfo = '';
        if ($this->debug) {
            $debugInfo = '<div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px; font-family: monospace; font-size: 12px; overflow-x: auto;">';
            $debugInfo .= '<h3>Debug Information</h3>';
            $debugInfo .= '<p><strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . '</p>';
            $debugInfo .= '<p><strong>Line:</strong> ' . $exception->getLine() . '</p>';
            $debugInfo .= '<h4>Stack Trace:</h4>';
            $debugInfo .= '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
            $debugInfo .= '</div>';
        }

        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f8f9fa; 
            color: #333; 
        }
        .container { 
            max-width: 800px; 
            margin: 50px auto; 
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
    <div class="container">
        <h1 class="error-code">' . $statusCode . '</h1>
        <h2 class="error-title">' . htmlspecialchars($title) . '</h2>
        <p class="error-message">' . htmlspecialchars($message) . '</p>
        <div class="back-link">
            <a href="/">← Back to Home</a>
        </div>
        ' . $debugInfo . '
    </div>
</body>
</html>';
    }

    /**
     * Get HTTP status code for exception
     */
    protected function getStatusCode(\Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        // Map common exceptions to status codes
        $statusMap = [
            'Ludelix\\Routing\\Exceptions\\RouteNotFoundException' => 404,
            'Ludelix\\Routing\\Exceptions\\MethodNotAllowedException' => 405,
            'Ludelix\\Ludou\\Exceptions\\TemplateNotFoundException' => 500,
            'Ludelix\\Database\\Exceptions\\QueryException' => 500,
        ];

        $exceptionClass = get_class($exception);
        return $statusMap[$exceptionClass] ?? 500;
    }

    /**
     * Get user-friendly title for exception
     */
    protected function getTitle(\Throwable $exception): string
    {
        $statusCode = $this->getStatusCode($exception);
        
        $titles = [
            404 => 'Page Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            429 => 'Too Many Requests',
            500 => 'Server Error',
            503 => 'Service Unavailable',
        ];

        return $titles[$statusCode] ?? 'Error';
    }

    /**
     * Get user-friendly message for exception
     */
    protected function getMessage(\Throwable $exception): string
    {
        if ($this->debug) {
            return $exception->getMessage();
        }

        $statusCode = $this->getStatusCode($exception);
        
        $messages = [
            404 => 'Sorry, the page you are looking for could not be found.',
            405 => 'The HTTP method is not allowed for this resource.',
            419 => 'Sorry, your session has expired. Please refresh and try again.',
            429 => 'Too many requests. Please try again later.',
            500 => 'Something went wrong on our end. Please try again later.',
            503 => 'The service is temporarily unavailable. Please try again later.',
        ];

        return $messages[$statusCode] ?? 'An error occurred while processing your request.';
    }

    /**
     * Check if request wants JSON response
     */
    protected function wantsJson(Request $request): bool
    {
        $accept = $request->getHeader('Accept') ?? '';
        return str_contains($accept, 'application/json') || 
               str_contains($accept, 'text/json') ||
               $request->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }
} 