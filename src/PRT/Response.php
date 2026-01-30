<?php

namespace Ludelix\PRT;

/**
 * HTTP Response
 * 
 * Represents an HTTP response with status, headers and content
 */
class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected mixed $content = '';
    protected array $cookies = [];

    public function __construct(mixed $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = array_merge([
            'Content-Type' => 'text/html; charset=UTF-8'
        ], $headers);
    }

    /**
     * Set response content
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get response content
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Set status code
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers
     * 
     * @param array $headers
     * @return self
     */
    public function headers(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, (string) $value);
        }
        return $this;
    }

    /**
     * Get header
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set cookie
     */
    public function setCookie(string $name, string $value, array $options = []): self
    {
        $this->cookies[$name] = array_merge([
            'value' => $value,
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Lax'
        ], $options);

        return $this;
    }

    /**
     * Create JSON response
     */
    public static function json(array $data, int $statusCode = 200): self
    {
        return new self(json_encode($data), $statusCode, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Create redirect response
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, [
            'Location' => $url
        ]);
    }

    /**
     * Create file download response
     */
    public static function download(string $filePath, ?string $filename = null): self
    {
        if (!file_exists($filePath)) {
            return new self('File not found', 404);
        }

        $filename = $filename ?? basename($filePath);
        $content = file_get_contents($filePath);

        return new self($content, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($content)
        ]);
    }

    /**
     * Create view response
     */
    public static function view(string $template, array $data = []): self
    {
        try {
            // Use Bridge to render the template
            $content = \Ludelix\Bridge\Bridge::render($template, $data);
            return new self($content);
        } catch (\Throwable $e) {
            // Se for erro de template não encontrado, mostrar página de erro elegante
            if (str_contains($e->getMessage(), 'não encontrado')) {
                $errorContent = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template não encontrado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333; 
        }
        .error-container { 
            max-width: 800px; 
            margin: 100px auto; 
            padding: 40px; 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
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
        .debug-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Template não encontrado</h2>
        <p class="error-message">O template solicitado não foi encontrado no sistema.</p>
        
        <div class="debug-info">
            <h5>Detalhes do erro:</h5>
            <p><strong>Template:</strong> ' . htmlspecialchars($template) . '</p>
            <p><strong>Erro:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <p><strong>Arquivo:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>
        </div>
        
        <div class="text-center mt-4">
            <a href="/" class="btn btn-primary">← Voltar ao Início</a>
        </div>
    </div>
</body>
</html>';

                return new self($errorContent, 404);
            }

            // Para outros erros, mostrar fallback básico
            $content = "<!-- Template Error: {$template} -->\n" .
                "<!-- Error: " . htmlspecialchars($e->getMessage()) . " -->\n" .
                json_encode($data);
            return new self($content, 500);
        }
    }

    /**
     * Send response to browser
     */
    public function send(): void
    {
        ob_start();
        $content = $this->content;
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        foreach ($this->cookies as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                $cookie['expires'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }
        echo $content;
        ob_end_flush();
    }

    /**
     * Check if response is successful
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is redirect
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if response is client error
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is server error
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return (string) $this->content;
    }
}