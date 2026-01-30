<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\ValueObjects;

/**
 * Representa o resultado de uma operação de upload
 * 
 * Objeto de valor imutável que encapsula o resultado completo
 * de um processo de upload, incluindo validação e armazenamento.
 */
readonly class UploadResult
{
    public function __construct(
        public bool $success,
        public ?StorageResult $storageResult = null,
        public ?ValidationResult $validationResult = null,
        public array $errors = [],
        public array $warnings = [],
        public ?string $uploadId = null,
        public array $metadata = [],
        public ?array $thumbnails = null,
        public float $processingTime = 0.0
    ) {}

    /**
     * Cria um resultado de upload bem-sucedido
     */
    public static function success(
        StorageResult $storageResult,
        ValidationResult $validationResult,
        ?string $uploadId = null,
        array $metadata = [],
        ?array $thumbnails = null,
        float $processingTime = 0.0
    ): self {
        return new self(
            success: true,
            storageResult: $storageResult,
            validationResult: $validationResult,
            errors: [],
            warnings: $validationResult->warnings,
            uploadId: $uploadId,
            metadata: $metadata,
            thumbnails: $thumbnails,
            processingTime: $processingTime
        );
    }

    /**
     * Cria um resultado de upload com falha
     */
    public static function failure(
        array $errors,
        ?ValidationResult $validationResult = null,
        ?string $uploadId = null,
        array $warnings = [],
        float $processingTime = 0.0
    ): self {
        return new self(
            success: false,
            storageResult: null,
            validationResult: $validationResult,
            errors: $errors,
            warnings: $warnings,
            uploadId: $uploadId,
            metadata: [],
            thumbnails: null,
            processingTime: $processingTime
        );
    }

    /**
     * Cria um resultado de falha de validação
     */
    public static function validationFailure(
        ValidationResult $validationResult,
        ?string $uploadId = null,
        float $processingTime = 0.0
    ): self {
        return new self(
            success: false,
            storageResult: null,
            validationResult: $validationResult,
            errors: $validationResult->errors,
            warnings: $validationResult->warnings,
            uploadId: $uploadId,
            metadata: [],
            thumbnails: null,
            processingTime: $processingTime
        );
    }

    /**
     * Verifica se o upload foi bem-sucedido
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Verifica se o upload falhou
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Verifica se há erros
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Verifica se há avisos
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Obtém todos os erros como string
     */
    public function getErrorsAsString(string $separator = '; '): string
    {
        return implode($separator, $this->errors);
    }

    /**
     * Obtém todos os avisos como string
     */
    public function getWarningsAsString(string $separator = '; '): string
    {
        return implode($separator, $this->warnings);
    }

    /**
     * Obtém a URL do arquivo se o upload foi bem-sucedido
     */
    public function getUrl(): ?string
    {
        return $this->storageResult?->url;
    }

    /**
     * Obtém o caminho do arquivo se o upload foi bem-sucedido
     */
    public function getPath(): ?string
    {
        return $this->storageResult?->path;
    }

    /**
     * Obtém o tamanho do arquivo se o upload foi bem-sucedido
     */
    public function getSize(): ?int
    {
        return $this->storageResult?->size;
    }

    /**
     * Obtém o tipo MIME do arquivo se o upload foi bem-sucedido
     */
    public function getMimeType(): ?string
    {
        return $this->storageResult?->mimeType;
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'storage_result' => $this->storageResult?->toArray(),
            'validation_result' => $this->validationResult?->toArray(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'upload_id' => $this->uploadId,
            'metadata' => $this->metadata,
            'thumbnails' => $this->thumbnails,
            'processing_time' => $this->processingTime,
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

