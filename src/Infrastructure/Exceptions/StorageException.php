<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Exceptions;

use Exception;

/**
 * Exceção base para operações de storage
 * 
 * Classe base para todas as exceções relacionadas ao sistema de storage,
 * fornecendo funcionalidades comuns e padronização de mensagens.
 */
class StorageException extends Exception
{
    protected string $operation;
    protected ?string $path;
    protected array $context;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        string $operation = '',
        ?string $path = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->operation = $operation;
        $this->path = $path;
        $this->context = $context;
    }

    /**
     * Cria exceção para operação de upload
     */
    public static function uploadFailed(string $message, ?string $path = null, array $context = []): self
    {
        return new self(
            message: "Falha no upload: {$message}",
            operation: 'upload',
            path: $path,
            context: $context
        );
    }

    /**
     * Cria exceção para operação de download
     */
    public static function downloadFailed(string $message, ?string $path = null, array $context = []): self
    {
        return new self(
            message: "Falha no download: {$message}",
            operation: 'download',
            path: $path,
            context: $context
        );
    }

    /**
     * Cria exceção para operação de delete
     */
    public static function deleteFailed(string $message, ?string $path = null, array $context = []): self
    {
        return new self(
            message: "Falha ao deletar: {$message}",
            operation: 'delete',
            path: $path,
            context: $context
        );
    }

    /**
     * Cria exceção para arquivo não encontrado
     */
    public static function fileNotFound(string $path, array $context = []): self
    {
        return new self(
            message: "Arquivo não encontrado: {$path}",
            operation: 'get',
            path: $path,
            context: $context
        );
    }

    /**
     * Cria exceção para configuração inválida
     */
    public static function invalidConfiguration(string $message, array $context = []): self
    {
        return new self(
            message: "Configuração inválida: {$message}",
            operation: 'config',
            context: $context
        );
    }

    /**
     * Cria exceção para provedor não suportado
     */
    public static function unsupportedProvider(string $provider, array $context = []): self
    {
        return new self(
            message: "Provedor não suportado: {$provider}",
            operation: 'provider',
            context: $context
        );
    }

    /**
     * Obtém a operação que causou a exceção
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * Obtém o caminho do arquivo relacionado à exceção
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Obtém o contexto adicional da exceção
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Converte a exceção para array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'operation' => $this->operation,
            'path' => $this->path,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }

    /**
     * Converte a exceção para JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

