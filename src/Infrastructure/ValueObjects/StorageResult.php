<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\ValueObjects;

/**
 * Representa o resultado de uma operação de armazenamento
 * 
 * Objeto de valor imutável que encapsula todas as informações
 * relevantes sobre o resultado de uma operação de storage.
 */
readonly class StorageResult
{
    public function __construct(
        public string $path,
        public string $url,
        public int $size,
        public string $mimeType,
        public array $metadata = [],
        public bool $success = true,
        public ?string $error = null,
        public ?string $hash = null,
        public ?string $originalName = null,
        public ?array $thumbnails = null
    ) {}

    /**
     * Cria um resultado de sucesso
     */
    public static function success(
        string $path,
        string $url,
        int $size,
        string $mimeType,
        array $metadata = [],
        ?string $hash = null,
        ?string $originalName = null,
        ?array $thumbnails = null
    ): self {
        return new self(
            path: $path,
            url: $url,
            size: $size,
            mimeType: $mimeType,
            metadata: $metadata,
            success: true,
            error: null,
            hash: $hash,
            originalName: $originalName,
            thumbnails: $thumbnails
        );
    }

    /**
     * Cria um resultado de falha
     */
    public static function failure(string $error, string $path = ''): self
    {
        return new self(
            path: $path,
            url: '',
            size: 0,
            mimeType: '',
            metadata: [],
            success: false,
            error: $error
        );
    }

    /**
     * Verifica se a operação foi bem-sucedida
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Verifica se a operação falhou
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Obtém a mensagem de erro se houver
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'url' => $this->url,
            'size' => $this->size,
            'mime_type' => $this->mimeType,
            'metadata' => $this->metadata,
            'success' => $this->success,
            'error' => $this->error,
            'hash' => $this->hash,
            'original_name' => $this->originalName,
            'thumbnails' => $this->thumbnails,
        ];
    }

    /**
     * Converte para JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

