<?php

declare(strict_types=1);

namespace Ludelix\Interface\Asset;

/**
 * Interface para gerenciamento de assets
 */
interface AssetManagerInterface
{
    /**
     * Gera URL para um asset
     */
    public function asset(string $path, bool $secure = null): string;

    /**
     * Gera URL para asset versionado
     */
    public function version(string $path): string;

    /**
     * Gera URL para asset com mix (Laravel Mix)
     */
    public function mix(string $path, string $manifestDirectory = ''): string;

    /**
     * Define o diretório base dos assets
     */
    public function setBasePath(string $path): self;

    /**
     * Define a URL base dos assets
     */
    public function setBaseUrl(string $url): self;

    /**
     * Adiciona um asset ao manifesto
     */
    public function addAsset(string $name, string $path, array $dependencies = []): self;

    /**
     * Remove um asset do manifesto
     */
    public function removeAsset(string $name): self;

    /**
     * Verifica se um asset existe
     */
    public function hasAsset(string $name): bool;

    /**
     * Obtém informações de um asset
     */
    public function getAsset(string $name): ?array;

    /**
     * Lista todos os assets
     */
    public function getAllAssets(): array;

    /**
     * Gera tags HTML para CSS
     */
    public function css(string|array $assets, array $attributes = []): string;

    /**
     * Gera tags HTML para JavaScript
     */
    public function js(string|array $assets, array $attributes = []): string;

    /**
     * Gera tag HTML para imagem
     */
    public function img(string $asset, array $attributes = []): string;

    /**
     * Compila e minifica assets
     */
    public function compile(array $assets = []): bool;

    /**
     * Limpa cache de assets
     */
    public function clearCache(): bool;
}

