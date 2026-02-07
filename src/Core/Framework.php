<?php

namespace Ludelix\Core;

use Ludelix\Interface\FrameworkInterface;
use Ludelix\Interface\DI\ContainerInterface;
use Ludelix\Bootstrap\Runtime\EnvironmentLoader;
use Ludelix\Bootstrap\Runtime\ServiceRegistrar;
use Ludelix\Core\Logging\FileLogger;

class Framework implements FrameworkInterface
{
    protected static ?Framework $instance = null;
    protected ContainerInterface $container;
    protected string $basePath;
    protected bool $booted = false;
    protected array $serviceProviders = [];

    public static function getInstance(): ?Framework
    {
        return static::$instance;
    }

    public static function setInstance(Framework $instance): void
    {
        static::$instance = $instance;
    }

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?: getcwd();
        $this->container = new Container();
        $this->registerBaseBindings();
        static::setInstance($this);
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->loadEnvironment();
        $this->registerServices();
        $this->bootServices();

        // Register the new FileLogger in the container and Bridge
        $logDir = $this->basePath . '/cubby/logs';
        $logger = new FileLogger($logDir, 'app.log', 30, 'Y-m-d H:i:s');
        $this->container()->instance('logger', $logger);
        $this->registerGlobalErrorHandlers($logger, $logDir);



        \Ludelix\Bridge\Bridge::instance($this->container());

        $this->booted = true;
    }

    public function run(): void
    {
        try {
            $this->boot();

            // Handle HTTP request or Console command
            if ($this->isConsole()) {
                $this->runConsole();
            } else {
                $this->runHttp();
            }
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    public function terminate(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'terminate')) {
                $provider->terminate();
            }
        }
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }

    public function version(): string
    {
        return Version::get();
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function configPath(): string
    {
        return $this->basePath . '/config';
    }

    public function storagePath(): string
    {
        return $this->basePath . '/cubby';
    }

    public function environment(): string
    {
        return $_ENV['APP_ENV'] ?? 'production';
    }

    /**
     * Generate a URL for a named route.
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function route(string $name, array $parameters = []): string
    {
        return $this->container->get('router')->url($name, $parameters);
    }

    /**
     * Get the current CSRF token.
     *
     * @return string
     */
    public function token(): string
    {
        return $this->container->get('csrf')->token();
    }

    /**
     * Get the cache manager instance.
     *
     * @return \Ludelix\Cache\CacheManager
     */
    public function cache(): mixed
    {
        return $this->container->get('cache');
    }

    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    public function isDebug(): bool
    {
        return (bool) ($_ENV['APP_DEBUG'] ?? false);
    }

    protected function registerBaseBindings(): void
    {
        $this->container->instance(FrameworkInterface::class, $this);
        $this->container->instance(ContainerInterface::class, $this->container);
        $this->container->instance('app', $this);
        $this->container->instance('container', $this->container);
        $this->container->instance('path.config', $this->configPath());
    }

    protected function loadEnvironment(): void
    {
        $loader = new EnvironmentLoader($this->basePath);
        $loader->load();
    }

    protected function registerServices(): void
    {
        $registrar = new ServiceRegistrar($this->container);
        $this->serviceProviders = $registrar->register();
    }

    protected function bootServices(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    protected function isConsole(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }

    protected function runConsole(): void
    {
        // Console handling will be implemented later
        echo "Console mode not implemented yet\n";
    }

    protected function runHttp(): void
    {
        try {
            if ($this->container->has('request.handler')) {
                error_log("RequestHandler found, using it");
                $handler = $this->container->get('request.handler');
                $response = $handler->process();
                $response->send();
            } else {
                error_log("RequestHandler not found, trying router");
                // Try to handle request with router
                if ($this->container->has('router')) {
                    $router = $this->container->get('router');
                    $request = $this->createRequest();
                    $response = $router->dispatch($request);
                    $response->send();
                } else {
                    error_log("Router not found, using fallback");
                    // Fallback
                    header('Content-Type: application/json');
                    echo json_encode([
                        'framework' => 'Ludelix Framework',
                        'version' => $this->version(),
                        'status' => 'running',
                        'message' => 'Router not available'
                    ]);
                }
            }
        } catch (\Throwable $e) {
            error_log("Exception in runHttp: " . $e->getMessage());
            $this->handleHttpException($e);
        }
    }

    protected function createRequest()
    {
        return new \Ludelix\PRT\Request(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET,
            $_POST,
            $_SERVER
        );
    }

    protected function handleHttpException(\Throwable $e): void
    {
        // Log the exception for debugging
        error_log("HTTP Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

        $debug = $this->isDebug();
        $wantsJson = $this->wantsJson();

        // Handle specific exceptions
        if ($e instanceof \Ludelix\Routing\Exceptions\RouteNotFoundException) {
            $this->renderError(404, 'Página não encontrada', $e->getMessage(), $wantsJson, $debug);
            return;
        }

        if ($e instanceof \Ludelix\Routing\Exceptions\MethodNotAllowedException) {
            $this->renderError(405, 'Método não permitido', $e->getMessage(), $wantsJson, $debug);
            return;
        }

        // Handle other exceptions
        $this->renderError(500, 'Erro interno do servidor', $e->getMessage(), $wantsJson, $debug);
    }

    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Se é uma requisição de API ou tem Accept: application/json
        if (
            str_contains($accept, 'application/json') ||
            str_contains($accept, 'text/json') ||
            $_SERVER['REQUEST_URI'] === '/api' ||
            str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')
        ) {
            return true;
        }

        // Se é um navegador (tem User-Agent e não é curl/wget)
        if (
            !empty($userAgent) &&
            !str_contains(strtolower($userAgent), 'curl') &&
            !str_contains(strtolower($userAgent), 'wget') &&
            !str_contains(strtolower($userAgent), 'postman')
        ) {
            return false;
        }

        // Padrão para ferramentas de linha de comando
        return true;
    }

    protected function renderError(int $statusCode, string $title, string $message, bool $wantsJson, bool $debug): void
    {
        if ($wantsJson) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => $title,
                'message' => $debug ? $message : 'Erro interno do servidor',
                'status' => $statusCode
            ]);
            return;
        }

        // Sempre usar template engine, mesmo se falhar
        if ($this->container->has('ludou')) {
            $ludou = $this->container->get('ludou');
            $template = "errors.{$statusCode}";

            try {
                if ($ludou->exists($template)) {
                    $content = $ludou->render($template, [
                        'title' => $title,
                        'message' => $debug ? $message : '',
                        'status' => $statusCode,
                        'debug' => $debug
                    ]);

                    http_response_code($statusCode);
                    header('Content-Type: text/html; charset=UTF-8');
                    echo $content;
                    return;
                }
            } catch (\Throwable $templateError) {
                // Se template falhar, logar mas não usar renderBasicError
                error_log("Template error: " . $templateError->getMessage());
            }
        }

        // Se não conseguir usar template, mostrar página mínima sem HTML complexo
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($title) . '</title></head><body><h1>' . $statusCode . '</h1><h2>' . htmlspecialchars($title) . '</h2><p>Ocorreu um erro inesperado.</p><a href="/">← Voltar ao Início</a></body></html>';
    }

    /**
     * Registra handlers globais de erro, exceção e shutdown usando o logger central.
     */
    protected function registerGlobalErrorHandlers($logger, $logDir): void
    {
        $debug = $this->isDebug();

        // Handler para erros PHP (E_ERROR, E_WARNING, E_NOTICE, etc)
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger, $logDir, $debug) {
            $msg = "[PHP ERROR $errno] $errstr em $errfile:$errline";
            try {
                $logger->error($msg);
            } catch (\Throwable $e) {
                $this->writeFallbackLog($msg, $logDir);
            }

            // Não exibir erro na tela se debug estiver desativado
            if (!$debug) {
                return true; // Suprime o erro
            }
        });

        // Handler para exceções (TypeError, Exception, etc)
        set_exception_handler(function ($exception) use ($logger, $logDir, $debug) {
            $msg = "[UNCAUGHT EXCEPTION] " . $exception->getMessage() . " em " . $exception->getFile() . ":" . $exception->getLine();

            // LOG FORÇADO: Garantir que qualquer exceção seja logada no mesmo arquivo
            $file = $logDir . '/app-' . date('Y-m-d') . '.log';
            file_put_contents($file, '[' . date('Y-m-d H:i:s') . "] $msg\n" . $exception->getTraceAsString() . "\n", FILE_APPEND | LOCK_EX);

            try {
                $logger->critical($msg, ['exception' => (string) $exception]);
            } catch (\Throwable $e) {
                $this->writeFallbackLog($msg, $logDir);
            }

            // Não exibir erro na tela se debug estiver desativado
            if (!$debug) {
                http_response_code(500);
                header('Content-Type: text/html; charset=UTF-8');
                echo '<!DOCTYPE html><html><head><title>Erro interno do servidor</title></head><body><h1>500</h1><h2>Erro interno do servidor</h2><p>Ocorreu um erro inesperado.</p><a href="/">← Voltar ao Início</a></body></html>';
                exit;
            }
        });

        // Handler para erros fatais (E_ERROR, E_PARSE, etc)
        register_shutdown_function(function () use ($logger, $logDir, $debug) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR, 1])) {
                $msg = "[FATAL ERROR] {$error['message']} em {$error['file']}:{$error['line']} (type: {$error['type']})";
                try {
                    $logger->critical($msg);
                } catch (\Throwable $e) {
                    $this->writeFallbackLog($msg, $logDir);
                }

                // Não exibir erro na tela se debug estiver desativado
                if (!$debug) {
                    http_response_code(500);
                    header('Content-Type: text/html; charset=UTF-8');
                    echo '<!DOCTYPE html><html><head><title>Erro interno do servidor</title></head><body><h1>500</h1><h2>Erro interno do servidor</h2><p>Ocorreu um erro inesperado.</p><a href="/">← Voltar ao Início</a></body></html>';
                    exit;
                }
            }
        });

        // Handler para warnings e notices (como Laravel)
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger, $logDir, $debug) {
            if (in_array($errno, [E_WARNING, E_NOTICE, E_DEPRECATED, E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED])) {
                $msg = "[PHP $errno] $errstr em $errfile:$errline";
                try {
                    $logger->warning($msg);
                } catch (\Throwable $e) {
                    $this->writeFallbackLog($msg, $logDir);
                }

                // Não exibir warning/notice na tela se debug estiver desativado
                if (!$debug) {
                    return true; // Suprime o warning/notice
                }
            }
        });
    }

    /**
     * Fallback mínimo para logar caso o logger não esteja disponível.
     */
    protected function writeFallbackLog($msg, $logDir): void
    {
        $file = rtrim($logDir, '/\\') . '/fatal-fallback-' . date('Y-m-d') . '.log';
        file_put_contents($file, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Log centralizado do framework - usa logger central ou fallback
     */
    protected function logError(\Throwable $e, string $context = ''): void
    {
        $msg = "[$context] " . get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine();
        try {
            $logger = $this->container()->has('logger') ? $this->container()->get('logger') : null;
            if ($logger) {
                $logger->critical($msg, [
                    'trace' => $e->getTraceAsString(),
                    'exception' => (string) $e
                ]);
            }
        } catch (\Throwable $logEx) {
            $this->writeFallbackLog($msg, $this->basePath . '/cubby/logs');
        }
    }

    /**
     * Loga detalhadamente qualquer exceção não capturada.
     */
    protected function handleException(\Throwable $e): void
    {
        $this->logError($e, 'UNCAUGHT THROWABLE');
        // Exibir erro usando renderError
        $this->renderError(500, 'Erro interno do servidor', $e->getMessage(), false, $this->isDebug());
    }
}