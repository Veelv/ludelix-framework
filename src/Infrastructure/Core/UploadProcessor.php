<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Core;

use Ludelix\Infrastructure\Contracts\UploadProcessorInterface;
use Ludelix\Infrastructure\Contracts\FileValidatorInterface;
use Ludelix\Infrastructure\Contracts\MetadataExtractorInterface;
use Ludelix\Infrastructure\Contracts\StorageInterface;
use Ludelix\Infrastructure\ValueObjects\UploadResult;
use Ludelix\Infrastructure\ValueObjects\UploadConfig;
use Ludelix\Infrastructure\ValueObjects\ChunkedUpload;
use Ludelix\Infrastructure\ValueObjects\ValidationResult;
use Ludelix\Infrastructure\Exceptions\UploadException;
use Ludelix\Infrastructure\Exceptions\ValidationException;
use Ludelix\PRT\UploadedFile;
use DateTimeImmutable;
use DateInterval;

/**
 * Implementação do processador de upload
 * 
 * Coordena todo o processo de upload, incluindo validação,
 * armazenamento, extração de metadados e processamento de chunks.
 */
class UploadProcessor implements UploadProcessorInterface
{
    private array $uploadSessions = [];
    private int $defaultChunkSize = 5 * 1024 * 1024; // 5MB
    private int $sessionExpirationHours = 24;

    public function __construct(
        private FileValidatorInterface $validator,
        private MetadataExtractorInterface $metadataExtractor,
        private StorageInterface $storage
    ) {}

    public function process(UploadedFile $file, UploadConfig $config): UploadResult
    {
        $startTime = microtime(true);
        $uploadId = $this->generateUploadId();

        try {
            // Validar arquivo
            $validationResult = $this->validateFile($file, $config);
            if (!$validationResult->isValid) {
                return UploadResult::validationFailure(
                    $validationResult,
                    $uploadId,
                    microtime(true) - $startTime
                );
            }

            // Gerar nome único para o arquivo
            $filename = $this->generateUniqueFilename($file, $config);
            $path = $this->buildFilePath($config->directory, $filename);

            // Armazenar arquivo
            $storageResult = $this->storage->store($file, $path, [
                'generate_hash' => $config->generateHash,
                'hash_algorithm' => $config->hashAlgorithm,
            ]);

            if (!$storageResult->isSuccess()) {
                return UploadResult::failure(
                    ['storage' => $storageResult->getError()],
                    $validationResult,
                    $uploadId,
                    [],
                    microtime(true) - $startTime
                );
            }

            // Extrair metadados se solicitado
            $metadata = [];
            if ($config->extractMetadata) {
                try {
                    $metadata = $this->metadataExtractor->extract($file);
                } catch (\Exception $e) {
                    // Não falhar o upload por erro de metadados
                    $metadata['metadata_error'] = $e->getMessage();
                }
            }

            // Gerar thumbnails se solicitado
            $thumbnails = null;
            if ($config->generateThumbnails && $this->isImage($file)) {
                try {
                    $thumbnails = $this->generateThumbnails($file, $config, $path);
                } catch (\Exception $e) {
                    // Não falhar o upload por erro de thumbnail
                    $metadata['thumbnail_error'] = $e->getMessage();
                }
            }

            // Atualizar resultado do storage com metadados e thumbnails
            $finalStorageResult = new \Ludelix\Infrastructure\ValueObjects\StorageResult(
                path: $storageResult->path,
                url: $storageResult->url,
                size: $storageResult->size,
                mimeType: $storageResult->mimeType,
                metadata: array_merge($storageResult->metadata, $metadata),
                success: true,
                error: null,
                hash: $storageResult->hash,
                originalName: $storageResult->originalName,
                thumbnails: $thumbnails
            );

            return UploadResult::success(
                $finalStorageResult,
                $validationResult,
                $uploadId,
                $metadata,
                $thumbnails,
                microtime(true) - $startTime
            );

        } catch (ValidationException $e) {
            return UploadResult::failure(
                $e->getValidationErrors(),
                null,
                $uploadId,
                [],
                microtime(true) - $startTime
            );
        } catch (\Exception $e) {
            return UploadResult::failure(
                ['processing' => $e->getMessage()],
                null,
                $uploadId,
                [],
                microtime(true) - $startTime
            );
        }
    }

    public function processChunked(ChunkedUpload $upload): UploadResult
    {
        $startTime = microtime(true);

        try {
            // Verificar se o upload expirou
            if ($upload->isExpired()) {
                throw UploadException::uploadExpired($upload->uploadId);
            }

            // Verificar se já está completo
            if ($upload->isCompleted()) {
                return $this->finalizeChunkedUpload($upload->uploadId);
            }

            // Atualizar status para uploading se ainda estiver pending
            if ($upload->isPending()) {
                $upload = $upload->withStatus('uploading');
                $this->uploadSessions[$upload->uploadId] = $upload;
            }

            return UploadResult::success(
                new \Ludelix\Infrastructure\ValueObjects\StorageResult(
                    path: '',
                    url: '',
                    size: $upload->uploadedSize,
                    mimeType: '',
                    metadata: ['upload_progress' => $upload->getProgress()],
                    success: true
                ),
                ValidationResult::valid(),
                $upload->uploadId,
                ['status' => 'in_progress', 'progress' => $upload->getProgress()],
                null,
                microtime(true) - $startTime
            );

        } catch (\Exception $e) {
            return UploadResult::failure(
                ['chunked_processing' => $e->getMessage()],
                null,
                $upload->uploadId,
                [],
                microtime(true) - $startTime
            );
        }
    }

    public function resumeUpload(string $uploadId): UploadResult
    {
        if (!isset($this->uploadSessions[$uploadId])) {
            throw UploadException::uploadNotFound($uploadId);
        }

        $upload = $this->uploadSessions[$uploadId];
        
        if ($upload->isExpired()) {
            unset($this->uploadSessions[$uploadId]);
            throw UploadException::uploadExpired($uploadId);
        }

        return $this->processChunked($upload);
    }

    public function cancelUpload(string $uploadId): bool
    {
        if (isset($this->uploadSessions[$uploadId])) {
            $upload = $this->uploadSessions[$uploadId];
            $upload = $upload->withStatus('cancelled');
            $this->uploadSessions[$uploadId] = $upload;
            
            // Limpar chunks temporários se existirem
            $this->cleanupChunks($uploadId);
            
            return true;
        }

        return false;
    }

    public function getUploadStatus(string $uploadId): array
    {
        if (!isset($this->uploadSessions[$uploadId])) {
            return ['status' => 'not_found'];
        }

        $upload = $this->uploadSessions[$uploadId];
        return $upload->toArray();
    }

    public function initializeChunkedUpload(string $filename, int $totalSize, UploadConfig $config): string
    {
        $uploadId = $this->generateUploadId();
        $chunkSize = $this->defaultChunkSize;
        $expiresAt = (new DateTimeImmutable())->add(new DateInterval("PT{$this->sessionExpirationHours}H"));

        $upload = ChunkedUpload::create(
            $uploadId,
            $filename,
            $totalSize,
            $chunkSize,
            $config,
            $expiresAt
        );

        $this->uploadSessions[$uploadId] = $upload;

        return $uploadId;
    }

    public function finalizeChunkedUpload(string $uploadId): UploadResult
    {
        if (!isset($this->uploadSessions[$uploadId])) {
            throw UploadException::uploadNotFound($uploadId);
        }

        $upload = $this->uploadSessions[$uploadId];
        $startTime = microtime(true);

        try {
            // Verificar se todos os chunks foram uploadados
            if (!$upload->isCompleted()) {
                throw UploadException::finalizationFailed(
                    'Upload incompleto',
                    $uploadId,
                    ['missing_chunks' => $upload->getMissingChunks()]
                );
            }

            // Combinar chunks em arquivo final
            $finalPath = $this->combineChunks($upload);
            
            // Criar arquivo temporário para validação e processamento
            $tempFile = $this->createTempFileFromPath($finalPath, $upload->filename);
            
            // Validar arquivo final
            $validationResult = $this->validateFile($tempFile, $upload->config);
            if (!$validationResult->isValid) {
                $this->cleanupChunks($uploadId);
                return UploadResult::validationFailure(
                    $validationResult,
                    $uploadId,
                    microtime(true) - $startTime
                );
            }

            // Mover para storage final
            $finalFilename = $this->generateUniqueFilename($tempFile, $upload->config);
            $storagePath = $this->buildFilePath($upload->config->directory, $finalFilename);
            
            $storageResult = $this->storage->store($tempFile, $storagePath, [
                'generate_hash' => $upload->config->generateHash,
                'hash_algorithm' => $upload->config->hashAlgorithm,
            ]);

            if (!$storageResult->isSuccess()) {
                $this->cleanupChunks($uploadId);
                return UploadResult::failure(
                    ['storage' => $storageResult->getError()],
                    $validationResult,
                    $uploadId,
                    [],
                    microtime(true) - $startTime
                );
            }

            // Extrair metadados
            $metadata = [];
            if ($upload->config->extractMetadata) {
                try {
                    $metadata = $this->metadataExtractor->extract($tempFile);
                } catch (\Exception $e) {
                    $metadata['metadata_error'] = $e->getMessage();
                }
            }

            // Limpar arquivos temporários
            $this->cleanupChunks($uploadId);
            unlink($finalPath);
            unset($this->uploadSessions[$uploadId]);

            return UploadResult::success(
                $storageResult,
                $validationResult,
                $uploadId,
                $metadata,
                null,
                microtime(true) - $startTime
            );

        } catch (\Exception $e) {
            $this->cleanupChunks($uploadId);
            throw UploadException::finalizationFailed(
                $e->getMessage(),
                $uploadId
            );
        }
    }

    public function cleanupExpiredUploads(): int
    {
        $cleaned = 0;
        $now = new DateTimeImmutable();

        foreach ($this->uploadSessions as $uploadId => $upload) {
            if ($upload->expiresAt < $now) {
                $this->cleanupChunks($uploadId);
                unset($this->uploadSessions[$uploadId]);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Valida um arquivo usando as regras configuradas
     */
    private function validateFile(UploadedFile $file, UploadConfig $config): ValidationResult
    {
        $rules = $config->validationRules;

        // Adicionar regras baseadas na configuração
        if (!empty($config->allowedMimeTypes)) {
            $rules['mime_types'] = $config->allowedMimeTypes;
        }

        if (!empty($config->allowedExtensions)) {
            $rules['extensions'] = $config->allowedExtensions;
        }

        if ($config->maxFileSize > 0) {
            $rules['max_size'] = $config->maxFileSize;
        }

        if ($config->minFileSize > 0) {
            $rules['min_size'] = $config->minFileSize;
        }

        return $this->validator->validate($file, $rules);
    }

    /**
     * Gera um nome único para o arquivo
     */
    private function generateUniqueFilename(UploadedFile $file, UploadConfig $config): string
    {
        if ($config->customFilename) {
            return $config->customFilename;
        }

        if ($config->preserveOriginalName) {
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            return $name . '_' . uniqid() . '.' . $extension;
        }

        $extension = $file->getClientOriginalExtension();
        return uniqid() . '.' . $extension;
    }

    /**
     * Constrói o caminho completo do arquivo
     */
    private function buildFilePath(string $directory, string $filename): string
    {
        return trim($directory, '/') . '/' . $filename;
    }

    /**
     * Verifica se o arquivo é uma imagem
     */
    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Gera thumbnails para imagens
     */
    private function generateThumbnails(UploadedFile $file, UploadConfig $config, string $originalPath): array
    {
        $thumbnails = [];
        
        foreach ($config->thumbnailSizes as $size) {
            try {
                $thumbnail = $this->createThumbnail($file, $size, $originalPath);
                $thumbnails[$size] = $thumbnail;
            } catch (\Exception $e) {
                // Continuar mesmo se um thumbnail falhar
                $thumbnails[$size] = ['error' => $e->getMessage()];
            }
        }

        return $thumbnails;
    }

    /**
     * Cria um thumbnail de uma imagem
     */
    private function createThumbnail(UploadedFile $file, string $size, string $originalPath): array
    {
        [$width, $height] = explode('x', $size);
        $width = (int) $width;
        $height = (int) $height;

        // Implementação básica - pode ser expandida com bibliotecas de imagem
        $thumbnailPath = str_replace('.', "_thumb_{$size}.", $originalPath);
        
        // Aqui seria implementada a lógica de redimensionamento
        // Por exemplo, usando GD ou ImageMagick
        
        return [
            'size' => $size,
            'path' => $thumbnailPath,
            'url' => $this->storage->url($thumbnailPath),
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Combina chunks em arquivo final
     */
    private function combineChunks(ChunkedUpload $upload): string
    {
        $finalPath = sys_get_temp_dir() . '/upload_' . $upload->uploadId . '_final';
        $finalFile = fopen($finalPath, 'wb');

        if (!$finalFile) {
            throw new \RuntimeException("Não foi possível criar arquivo final");
        }

        try {
            for ($i = 0; $i < $upload->totalChunks; $i++) {
                $chunkPath = $this->getChunkPath($upload->uploadId, $i);
                if (file_exists($chunkPath)) {
                    $chunkData = file_get_contents($chunkPath);
                    fwrite($finalFile, $chunkData);
                }
            }
        } finally {
            fclose($finalFile);
        }

        return $finalPath;
    }

    /**
     * Cria arquivo temporário a partir de um caminho
     */
    private function createTempFileFromPath(string $path, string $originalName): UploadedFile
    {
        // Esta é uma implementação simplificada
        // Na prática, seria necessário criar um objeto UploadedFile válido
        return new class($path, $originalName) extends UploadedFile {
            public function __construct(private string $path, private string $originalName) {}
            public function getPathname(): string { return $this->path; }
            public function getClientOriginalName(): string { return $this->originalName; }
            public function getClientOriginalExtension(): string { return pathinfo($this->originalName, PATHINFO_EXTENSION); }
            public function getMimeType(): string { 
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $this->path);
                finfo_close($finfo);
                return $mime ?: 'application/octet-stream';
            }
            public function getSize(): int { return filesize($this->path); }
            public function isValid(): bool { return file_exists($this->path); }
        };
    }

    /**
     * Obtém o caminho de um chunk
     */
    private function getChunkPath(string $uploadId, int $chunkNumber): string
    {
        return sys_get_temp_dir() . "/chunk_{$uploadId}_{$chunkNumber}";
    }

    /**
     * Limpa chunks temporários de um upload
     */
    private function cleanupChunks(string $uploadId): void
    {
        $pattern = sys_get_temp_dir() . "/chunk_{$uploadId}_*";
        foreach (glob($pattern) as $chunkFile) {
            unlink($chunkFile);
        }
    }

    /**
     * Gera ID único para upload
     */
    private function generateUploadId(): string
    {
        return uniqid('upload_', true);
    }
}

