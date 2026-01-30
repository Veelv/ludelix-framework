<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\ValueObjects;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * Representa dados de um upload chunked
 * 
 * Objeto de valor imutável que encapsula informações sobre
 * um upload dividido em chunks para arquivos grandes.
 */
readonly class ChunkedUpload
{
    public function __construct(
        public string $uploadId,
        public string $filename,
        public int $totalSize,
        public int $chunkSize,
        public int $currentChunk,
        public int $totalChunks,
        public int $uploadedSize,
        public string $status,
        public UploadConfig $config,
        public DateTimeInterface $createdAt,
        public DateTimeInterface $expiresAt,
        public array $uploadedChunks = [],
        public array $metadata = [],
        public ?string $hash = null
    ) {}

    /**
     * Cria uma nova sessão de upload chunked
     */
    public static function create(
        string $uploadId,
        string $filename,
        int $totalSize,
        int $chunkSize,
        UploadConfig $config,
        DateTimeInterface $expiresAt
    ): self {
        $totalChunks = (int) ceil($totalSize / $chunkSize);

        return new self(
            uploadId: $uploadId,
            filename: $filename,
            totalSize: $totalSize,
            chunkSize: $chunkSize,
            currentChunk: 0,
            totalChunks: $totalChunks,
            uploadedSize: 0,
            status: 'pending',
            config: $config,
            createdAt: new DateTimeImmutable(),
            expiresAt: $expiresAt,
            uploadedChunks: [],
            metadata: []
        );
    }

    /**
     * Marca um chunk como uploadado
     */
    public function withChunkUploaded(int $chunkNumber, int $chunkSize): self
    {
        $uploadedChunks = $this->uploadedChunks;
        $uploadedChunks[$chunkNumber] = $chunkSize;

        $uploadedSize = array_sum($uploadedChunks);
        $currentChunk = max(array_keys($uploadedChunks)) + 1;
        
        $status = $uploadedSize >= $this->totalSize ? 'completed' : 'uploading';

        return new self(
            uploadId: $this->uploadId,
            filename: $this->filename,
            totalSize: $this->totalSize,
            chunkSize: $this->chunkSize,
            currentChunk: $currentChunk,
            totalChunks: $this->totalChunks,
            uploadedSize: $uploadedSize,
            status: $status,
            config: $this->config,
            createdAt: $this->createdAt,
            expiresAt: $this->expiresAt,
            uploadedChunks: $uploadedChunks,
            metadata: $this->metadata,
            hash: $this->hash
        );
    }

    /**
     * Atualiza o status do upload
     */
    public function withStatus(string $status): self
    {
        return new self(
            uploadId: $this->uploadId,
            filename: $this->filename,
            totalSize: $this->totalSize,
            chunkSize: $this->chunkSize,
            currentChunk: $this->currentChunk,
            totalChunks: $this->totalChunks,
            uploadedSize: $this->uploadedSize,
            status: $status,
            config: $this->config,
            createdAt: $this->createdAt,
            expiresAt: $this->expiresAt,
            uploadedChunks: $this->uploadedChunks,
            metadata: $this->metadata,
            hash: $this->hash
        );
    }

    /**
     * Adiciona metadados ao upload
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            uploadId: $this->uploadId,
            filename: $this->filename,
            totalSize: $this->totalSize,
            chunkSize: $this->chunkSize,
            currentChunk: $this->currentChunk,
            totalChunks: $this->totalChunks,
            uploadedSize: $this->uploadedSize,
            status: $this->status,
            config: $this->config,
            createdAt: $this->createdAt,
            expiresAt: $this->expiresAt,
            uploadedChunks: $this->uploadedChunks,
            metadata: array_merge($this->metadata, $metadata),
            hash: $this->hash
        );
    }

    /**
     * Define o hash do arquivo
     */
    public function withHash(string $hash): self
    {
        return new self(
            uploadId: $this->uploadId,
            filename: $this->filename,
            totalSize: $this->totalSize,
            chunkSize: $this->chunkSize,
            currentChunk: $this->currentChunk,
            totalChunks: $this->totalChunks,
            uploadedSize: $this->uploadedSize,
            status: $this->status,
            config: $this->config,
            createdAt: $this->createdAt,
            expiresAt: $this->expiresAt,
            uploadedChunks: $this->uploadedChunks,
            metadata: $this->metadata,
            hash: $hash
        );
    }

    /**
     * Verifica se o upload está completo
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->uploadedSize >= $this->totalSize;
    }

    /**
     * Verifica se o upload expirou
     */
    public function isExpired(): bool
    {
        return new DateTimeImmutable() > $this->expiresAt;
    }

    /**
     * Verifica se o upload está em andamento
     */
    public function isInProgress(): bool
    {
        return $this->status === 'uploading';
    }

    /**
     * Verifica se o upload está pendente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verifica se o upload falhou
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Calcula o progresso do upload (0-100)
     */
    public function getProgress(): float
    {
        if ($this->totalSize === 0) {
            return 0.0;
        }

        return min(100.0, ($this->uploadedSize / $this->totalSize) * 100);
    }

    /**
     * Obtém os chunks que ainda precisam ser uploadados
     */
    public function getMissingChunks(): array
    {
        $missing = [];
        for ($i = 0; $i < $this->totalChunks; $i++) {
            if (!isset($this->uploadedChunks[$i])) {
                $missing[] = $i;
            }
        }
        return $missing;
    }

    /**
     * Obtém o próximo chunk a ser uploadado
     */
    public function getNextChunk(): ?int
    {
        $missing = $this->getMissingChunks();
        return empty($missing) ? null : $missing[0];
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'upload_id' => $this->uploadId,
            'filename' => $this->filename,
            'total_size' => $this->totalSize,
            'chunk_size' => $this->chunkSize,
            'current_chunk' => $this->currentChunk,
            'total_chunks' => $this->totalChunks,
            'uploaded_size' => $this->uploadedSize,
            'status' => $this->status,
            'config' => $this->config->toArray(),
            'created_at' => $this->createdAt->format('c'),
            'expires_at' => $this->expiresAt->format('c'),
            'uploaded_chunks' => $this->uploadedChunks,
            'metadata' => $this->metadata,
            'hash' => $this->hash,
            'progress' => $this->getProgress(),
            'is_completed' => $this->isCompleted(),
            'is_expired' => $this->isExpired(),
            'missing_chunks' => $this->getMissingChunks(),
            'next_chunk' => $this->getNextChunk(),
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

