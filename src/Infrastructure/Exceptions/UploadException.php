<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Exceptions;

/**
 * Exceção para falhas de upload
 * 
 * Especialização da StorageException para casos específicos
 * de falha durante o processo de upload.
 */
class UploadException extends StorageException
{
    private ?string $uploadId;
    private string $uploadStage;
    private array $uploadContext;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $path = null,
        ?string $uploadId = null,
        string $uploadStage = 'unknown',
        array $uploadContext = [],
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, 'upload', $path, $context);
        
        $this->uploadId = $uploadId;
        $this->uploadStage = $uploadStage;
        $this->uploadContext = $uploadContext;
    }

    /**
     * Cria exceção para falha na inicialização do upload
     */
    public static function initializationFailed(
        string $reason,
        ?string $uploadId = null,
        array $context = []
    ): self {
        return new self(
            message: "Falha na inicialização do upload: {$reason}",
            uploadId: $uploadId,
            uploadStage: 'initialization',
            uploadContext: $context
        );
    }

    /**
     * Cria exceção para falha no processamento de chunk
     */
    public static function chunkProcessingFailed(
        string $reason,
        int $chunkNumber,
        ?string $uploadId = null,
        array $context = []
    ): self {
        return new self(
            message: "Falha no processamento do chunk {$chunkNumber}: {$reason}",
            uploadId: $uploadId,
            uploadStage: 'chunk_processing',
            uploadContext: array_merge($context, ['chunk_number' => $chunkNumber])
        );
    }

    /**
     * Cria exceção para falha na finalização do upload
     */
    public static function finalizationFailed(
        string $reason,
        ?string $uploadId = null,
        array $context = []
    ): self {
        return new self(
            message: "Falha na finalização do upload: {$reason}",
            uploadId: $uploadId,
            uploadStage: 'finalization',
            uploadContext: $context
        );
    }

    /**
     * Cria exceção para upload expirado
     */
    public static function uploadExpired(?string $uploadId = null, array $context = []): self
    {
        return new self(
            message: "Upload expirado",
            uploadId: $uploadId,
            uploadStage: 'expired',
            uploadContext: $context
        );
    }

    /**
     * Cria exceção para upload cancelado
     */
    public static function uploadCancelled(?string $uploadId = null, array $context = []): self
    {
        return new self(
            message: "Upload cancelado",
            uploadId: $uploadId,
            uploadStage: 'cancelled',
            uploadContext: $context
        );
    }

    /**
     * Cria exceção para upload não encontrado
     */
    public static function uploadNotFound(string $uploadId, array $context = []): self
    {
        return new self(
            message: "Upload não encontrado: {$uploadId}",
            uploadId: $uploadId,
            uploadStage: 'not_found',
            uploadContext: $context
        );
    }

    /**
     * Cria exceção para falha na transferência
     */
    public static function transferFailed(
        string $reason,
        ?string $path = null,
        ?string $uploadId = null,
        array $context = []
    ): self {
        return new self(
            message: "Falha na transferência: {$reason}",
            path: $path,
            uploadId: $uploadId,
            uploadStage: 'transfer',
            uploadContext: $context
        );
    }

    /**
     * Cria exceção para quota excedida
     */
    public static function quotaExceeded(
        int $currentUsage,
        int $quota,
        ?string $uploadId = null,
        array $context = []
    ): self {
        return new self(
            message: "Quota de armazenamento excedida. Uso atual: {$currentUsage}, Quota: {$quota}",
            uploadId: $uploadId,
            uploadStage: 'quota_check',
            uploadContext: array_merge($context, [
                'current_usage' => $currentUsage,
                'quota' => $quota
            ])
        );
    }

    /**
     * Cria exceção para falha na conexão com provedor
     */
    public static function providerConnectionFailed(
        string $provider,
        string $reason,
        ?string $uploadId = null,
        array $context = []
    ): self {
        return new self(
            message: "Falha na conexão com o provedor {$provider}: {$reason}",
            uploadId: $uploadId,
            uploadStage: 'provider_connection',
            uploadContext: array_merge($context, ['provider' => $provider])
        );
    }

    /**
     * Obtém o ID do upload
     */
    public function getUploadId(): ?string
    {
        return $this->uploadId;
    }

    /**
     * Obtém o estágio do upload onde ocorreu a falha
     */
    public function getUploadStage(): string
    {
        return $this->uploadStage;
    }

    /**
     * Obtém o contexto específico do upload
     */
    public function getUploadContext(): array
    {
        return $this->uploadContext;
    }

    /**
     * Verifica se a exceção é recuperável
     */
    public function isRecoverable(): bool
    {
        $recoverableStages = [
            'chunk_processing',
            'transfer',
            'provider_connection'
        ];

        return in_array($this->uploadStage, $recoverableStages);
    }

    /**
     * Converte a exceção para array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'upload_id' => $this->uploadId,
            'upload_stage' => $this->uploadStage,
            'upload_context' => $this->uploadContext,
            'is_recoverable' => $this->isRecoverable(),
        ]);
    }
}

