<?php

declare(strict_types=1);

namespace Ludelix\Interface\Asset;

use Ludelix\PRT\Response;

/**
 * Interface para gerenciamento de redirecionamentos
 */
interface RedirectManagerInterface
{
    /**
     * Redireciona para uma URL
     */
    public function to(string $path, int $status = 302, array $headers = []): Response;

    /**
     * Redireciona para uma rota nomeada
     */
    public function route(string $route, array $parameters = [], int $status = 302, array $headers = []): Response;

    /**
     * Redireciona para uma ação de controller
     */
    public function action(string $action, array $parameters = [], int $status = 302, array $headers = []): Response;

    /**
     * Redireciona de volta (página anterior)
     */
    public function back(string $fallback = '/', int $status = 302, array $headers = []): Response;

    /**
     * Redireciona para home
     */
    public function home(int $status = 302, array $headers = []): Response;

    /**
     * Redireciona para URL externa
     */
    public function away(string $path, int $status = 302, array $headers = []): Response;

    /**
     * Redireciona com dados da sessão
     */
    public function with(array $data): self;

    /**
     * Redireciona com dados de input
     */
    public function withInput(array $input = null): self;

    /**
     * Redireciona com erros
     */
    public function withErrors(array $errors, string $key = 'default'): self;

    /**
     * Redireciona com mensagem de sucesso
     */
    public function withSuccess(string $message): self;

    /**
     * Redireciona com mensagem de erro
     */
    public function withError(string $message): self;

    /**
     * Redireciona com mensagem de info
     */
    public function withInfo(string $message): self;

    /**
     * Redireciona com mensagem de warning
     */
    public function withWarning(string $message): self;

    /**
     * Define headers customizados
     */
    public function withHeaders(array $headers): self;

    /**
     * Define cookies para o redirect
     */
    public function withCookies(array $cookies): self;

    /**
     * Redireciona permanentemente (301)
     */
    public function permanent(string $path, array $headers = []): Response;

    /**
     * Redireciona temporariamente (302)
     */
    public function temporary(string $path, array $headers = []): Response;
}

