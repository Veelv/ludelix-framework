<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\ValueObjects;

/**
 * Encapsula configurações para uma operação de upload
 * 
 * Objeto de valor imutável que define como um upload deve ser processado,
 * incluindo validações, transformações e opções específicas.
 */
readonly class UploadConfig
{
    public function __construct(
        public string $disk,
        public string $directory,
        public array $validationRules,
        public array $processingOptions = [],
        public bool $generateThumbnails = false,
        public array $thumbnailSizes = [],
        public bool $extractMetadata = true,
        public bool $generateHash = true,
        public string $hashAlgorithm = 'sha256',
        public bool $overwriteExisting = false,
        public ?string $customFilename = null,
        public array $allowedMimeTypes = [],
        public array $allowedExtensions = [],
        public int $maxFileSize = 0,
        public int $minFileSize = 0,
        public bool $preserveOriginalName = false,
        public array $imageTransformations = [],
        public bool $asyncProcessing = false,
        public ?string $tenantId = null
    ) {}

    /**
     * Cria configuração padrão para imagens
     */
    public static function forImages(
        string $disk = 'local',
        string $directory = 'images',
        array $thumbnailSizes = ['150x150', '300x300', '600x600']
    ): self {
        return new self(
            disk: $disk,
            directory: $directory,
            validationRules: ['image', 'max_size:10MB'],
            generateThumbnails: true,
            thumbnailSizes: $thumbnailSizes,
            allowedMimeTypes: [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml'
            ],
            allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            maxFileSize: 10 * 1024 * 1024 // 10MB
        );
    }

    /**
     * Cria configuração padrão para documentos
     */
    public static function forDocuments(
        string $disk = 'local',
        string $directory = 'documents'
    ): self {
        return new self(
            disk: $disk,
            directory: $directory,
            validationRules: ['document', 'max_size:50MB'],
            generateThumbnails: false,
            allowedMimeTypes: [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'text/csv'
            ],
            allowedExtensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'],
            maxFileSize: 50 * 1024 * 1024 // 50MB
        );
    }

    /**
     * Cria configuração padrão para vídeos
     */
    public static function forVideos(
        string $disk = 'local',
        string $directory = 'videos'
    ): self {
        return new self(
            disk: $disk,
            directory: $directory,
            validationRules: ['video', 'max_size:500MB'],
            generateThumbnails: true,
            thumbnailSizes: ['320x240', '640x480', '1280x720'],
            allowedMimeTypes: [
                'video/mp4',
                'video/avi',
                'video/quicktime',
                'video/x-msvideo',
                'video/webm'
            ],
            allowedExtensions: ['mp4', 'avi', 'mov', 'webm'],
            maxFileSize: 500 * 1024 * 1024, // 500MB
            asyncProcessing: true
        );
    }

    /**
     * Cria configuração personalizada
     */
    public static function custom(array $config): self
    {
        return new self(
            disk: $config['disk'] ?? 'local',
            directory: $config['directory'] ?? '',
            validationRules: $config['validation_rules'] ?? [],
            processingOptions: $config['processing_options'] ?? [],
            generateThumbnails: $config['generate_thumbnails'] ?? false,
            thumbnailSizes: $config['thumbnail_sizes'] ?? [],
            extractMetadata: $config['extract_metadata'] ?? true,
            generateHash: $config['generate_hash'] ?? true,
            hashAlgorithm: $config['hash_algorithm'] ?? 'sha256',
            overwriteExisting: $config['overwrite_existing'] ?? false,
            customFilename: $config['custom_filename'] ?? null,
            allowedMimeTypes: $config['allowed_mime_types'] ?? [],
            allowedExtensions: $config['allowed_extensions'] ?? [],
            maxFileSize: $config['max_file_size'] ?? 0,
            minFileSize: $config['min_file_size'] ?? 0,
            preserveOriginalName: $config['preserve_original_name'] ?? false,
            imageTransformations: $config['image_transformations'] ?? [],
            asyncProcessing: $config['async_processing'] ?? false,
            tenantId: $config['tenant_id'] ?? null
        );
    }

    /**
     * Cria uma nova instância com disk alterado
     */
    public function withDisk(string $disk): self
    {
        return new self(
            disk: $disk,
            directory: $this->directory,
            validationRules: $this->validationRules,
            processingOptions: $this->processingOptions,
            generateThumbnails: $this->generateThumbnails,
            thumbnailSizes: $this->thumbnailSizes,
            extractMetadata: $this->extractMetadata,
            generateHash: $this->generateHash,
            hashAlgorithm: $this->hashAlgorithm,
            overwriteExisting: $this->overwriteExisting,
            customFilename: $this->customFilename,
            allowedMimeTypes: $this->allowedMimeTypes,
            allowedExtensions: $this->allowedExtensions,
            maxFileSize: $this->maxFileSize,
            minFileSize: $this->minFileSize,
            preserveOriginalName: $this->preserveOriginalName,
            imageTransformations: $this->imageTransformations,
            asyncProcessing: $this->asyncProcessing,
            tenantId: $this->tenantId
        );
    }

    /**
     * Cria uma nova instância com diretório alterado
     */
    public function withDirectory(string $directory): self
    {
        return new self(
            disk: $this->disk,
            directory: $directory,
            validationRules: $this->validationRules,
            processingOptions: $this->processingOptions,
            generateThumbnails: $this->generateThumbnails,
            thumbnailSizes: $this->thumbnailSizes,
            extractMetadata: $this->extractMetadata,
            generateHash: $this->generateHash,
            hashAlgorithm: $this->hashAlgorithm,
            overwriteExisting: $this->overwriteExisting,
            customFilename: $this->customFilename,
            allowedMimeTypes: $this->allowedMimeTypes,
            allowedExtensions: $this->allowedExtensions,
            maxFileSize: $this->maxFileSize,
            minFileSize: $this->minFileSize,
            preserveOriginalName: $this->preserveOriginalName,
            imageTransformations: $this->imageTransformations,
            asyncProcessing: $this->asyncProcessing,
            tenantId: $this->tenantId
        );
    }

    /**
     * Cria uma nova instância com tenant ID
     */
    public function withTenant(string $tenantId): self
    {
        return new self(
            disk: $this->disk,
            directory: $this->directory,
            validationRules: $this->validationRules,
            processingOptions: $this->processingOptions,
            generateThumbnails: $this->generateThumbnails,
            thumbnailSizes: $this->thumbnailSizes,
            extractMetadata: $this->extractMetadata,
            generateHash: $this->generateHash,
            hashAlgorithm: $this->hashAlgorithm,
            overwriteExisting: $this->overwriteExisting,
            customFilename: $this->customFilename,
            allowedMimeTypes: $this->allowedMimeTypes,
            allowedExtensions: $this->allowedExtensions,
            maxFileSize: $this->maxFileSize,
            minFileSize: $this->minFileSize,
            preserveOriginalName: $this->preserveOriginalName,
            imageTransformations: $this->imageTransformations,
            asyncProcessing: $this->asyncProcessing,
            tenantId: $tenantId
        );
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'directory' => $this->directory,
            'validation_rules' => $this->validationRules,
            'processing_options' => $this->processingOptions,
            'generate_thumbnails' => $this->generateThumbnails,
            'thumbnail_sizes' => $this->thumbnailSizes,
            'extract_metadata' => $this->extractMetadata,
            'generate_hash' => $this->generateHash,
            'hash_algorithm' => $this->hashAlgorithm,
            'overwrite_existing' => $this->overwriteExisting,
            'custom_filename' => $this->customFilename,
            'allowed_mime_types' => $this->allowedMimeTypes,
            'allowed_extensions' => $this->allowedExtensions,
            'max_file_size' => $this->maxFileSize,
            'min_file_size' => $this->minFileSize,
            'preserve_original_name' => $this->preserveOriginalName,
            'image_transformations' => $this->imageTransformations,
            'async_processing' => $this->asyncProcessing,
            'tenant_id' => $this->tenantId,
        ];
    }
}

