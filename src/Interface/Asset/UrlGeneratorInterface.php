<?php

declare(strict_types=1);

namespace Ludelix\Interface\Asset;

/**
 * Interface para geração de URLs
 */
interface UrlGeneratorInterface
{
    /**
     * Gera URL para uma rota nomeada
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string;

    /**
     * Gera URL para uma ação de controller
     */
    public function action(string $action, array $parameters = [], bool $absolute = true): string;

    /**
     * Gera URL absoluta
     */
    public function to(string $path, array $extra = [], bool $secure = null): string;

    /**
     * Gera URL segura (HTTPS)
     */
    public function secure(string $path, array $parameters = []): string;

    /**
     * Gera URL para asset
     */
    public function asset(string $path, bool $secure = null): string;

    /**
     * Gera URL relativa
     */
    public function relative(string $path, array $parameters = []): string;

    /**
     * Obtém URL atual
     */
    public function current(): string;

    /**
     * Obtém URL anterior
     */
    public function previous(string $fallback = '/'): string;

    /**
     * Verifica se a URL atual corresponde ao padrão
     */
    public function is(string $pattern): bool;

    /**
     * Define o esquema padrão (http/https)
     */
    public function forceScheme(string $scheme): void;

    /**
     * Define o domínio raiz
     */
    public function forceRootUrl(string $root): void;

    /**
     * Gera query string a partir de array
     */
    public function query(array $parameters): string;

    /**
     * Adiciona parâmetros à URL atual
     */
    public function withParameters(array $parameters): string;

    /**
     * Remove parâmetros da URL atual
     */
    public function withoutParameters(array $parameters): string;
}

