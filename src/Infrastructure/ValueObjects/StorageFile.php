<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\ValueObjects;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * Representa um arquivo armazenado no sistema de storage
 * 
 * Objeto de valor imutável que encapsula todas as informações
 * sobre um arquivo armazenado.
 */
readonly class StorageFile
{
    public function __construct(
        public string $path,
        public string $url,
        public int $size,
        public string $mimeType,
        public DateTimeInterface $lastModified,
        public array $metadata = [],
        public ?string $hash = null,
        public ?string $originalName = null,
        public ?string $content = null
    ) {}

    /**
     * Cria uma instância a partir de dados do provedor
     */
    public static function fromProviderData(array $data): self
    {
        return new self(
            path: $data['path'],
            url: $data['url'],
            size: $data['size'],
            mimeType: $data['mime_type'],
            lastModified: $data['last_modified'] instanceof DateTimeInterface 
                ? $data['last_modified'] 
                : new DateTimeImmutable($data['last_modified']),
            metadata: $data['metadata'] ?? [],
            hash: $data['hash'] ?? null,
            originalName: $data['original_name'] ?? null,
            content: $data['content'] ?? null
        );
    }

    /**
     * Verifica se é um arquivo de imagem
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    /**
     * Verifica se é um arquivo de vídeo
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mimeType, 'video/');
    }

    /**
     * Verifica se é um arquivo de áudio
     */
    public function isAudio(): bool
    {
        return str_starts_with($this->mimeType, 'audio/');
    }

    /**
     * Verifica se é um documento
     */
    public function isDocument(): bool
    {
        return in_array($this->mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ]);
    }

    /**
     * Obtém a extensão do arquivo
     */
    public function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Obtém o nome do arquivo sem extensão
     */
    public function getBasename(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Obtém o diretório do arquivo
     */
    public function getDirectory(): string
    {
        return pathinfo($this->path, PATHINFO_DIRNAME);
    }

    /**
     * Obtém o tamanho formatado para leitura humana
     */
    public function getHumanReadableSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
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
            'last_modified' => $this->lastModified->format('c'),
            'metadata' => $this->metadata,
            'hash' => $this->hash,
            'original_name' => $this->originalName,
            'extension' => $this->getExtension(),
            'basename' => $this->getBasename(),
            'directory' => $this->getDirectory(),
            'human_readable_size' => $this->getHumanReadableSize(),
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'is_audio' => $this->isAudio(),
            'is_document' => $this->isDocument(),
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

