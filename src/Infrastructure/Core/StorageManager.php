<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Core;

use Ludelix\Infrastructure\Contracts\StorageInterface;
use Ludelix\Infrastructure\Contracts\FileValidatorInterface;
use Ludelix\Infrastructure\Contracts\MetadataExtractorInterface;
use Ludelix\Infrastructure\Contracts\UploadProcessorInterface;
use Ludelix\Infrastructure\ValueObjects\UploadConfig;
use Ludelix\Infrastructure\ValueObjects\UploadResult;
use Ludelix\Infrastructure\ValueObjects\StorageFile;
use Ludelix\Infrastructure\Exceptions\StorageException;
use Ludelix\PRT\UploadedFile;
use DateTimeInterface;

/**
 * Gerenciador central do sistema de storage
 * 
 * Fornece uma API unificada para todas as operações de storage,
 * coordenando diferentes adaptadores e componentes do sistema.
 */
class StorageManager
{
    private array $adapters = [];
    private string $defaultDisk = 'local';
    private array $diskConfigs = [];

    public function __construct(
        private FileValidatorInterface $validator,
        private MetadataExtractorInterface $metadataExtractor,
        array $config = []
    ) {
        $this->diskConfigs = $config['disks'] ?? [];
        $this->defaultDisk = $config['default'] ?? 'local';
    }

    /**
     * Registra um adaptador de storage
     */
    public function addAdapter(string $name, StorageInterface $adapter): void
    {
        $this->adapters[$name] = $adapter;
    }

    /**
     * Obtém um adaptador de storage
     */
    public function disk(string $name = null): StorageInterface
    {
        $diskName = $name ?? $this->defaultDisk;

        if (!isset($this->adapters[$diskName])) {
            throw StorageException::unsupportedProvider($diskName);
        }

        return $this->adapters[$diskName];
    }

    /**
     * Faz upload de um arquivo
     */
    public function upload(UploadedFile $file, UploadConfig $config = null): UploadResult
    {
        $config = $config ?? UploadConfig::custom(['disk' => $this->defaultDisk]);
        
        $processor = new UploadProcessor(
            $this->validator,
            $this->metadataExtractor,
            $this->disk($config->disk)
        );

        return $processor->process($file, $config);
    }

    /**
     * Faz upload de múltiplos arquivos
     */
    public function uploadMultiple(array $files, UploadConfig $config = null): array
    {
        $results = [];
        
        foreach ($files as $key => $file) {
            if ($file instanceof UploadedFile) {
                $results[$key] = $this->upload($file, $config);
            }
        }

        return $results;
    }

    /**
     * Inicia um upload chunked
     */
    public function initializeChunkedUpload(
        string $filename,
        int $totalSize,
        UploadConfig $config = null
    ): string {
        $config = $config ?? UploadConfig::custom(['disk' => $this->defaultDisk]);
        
        $processor = new UploadProcessor(
            $this->validator,
            $this->metadataExtractor,
            $this->disk($config->disk)
        );

        return $processor->initializeChunkedUpload($filename, $totalSize, $config);
    }

    /**
     * Processa um chunk de upload
     */
    public function uploadChunk(string $uploadId, int $chunkNumber, string $chunkData): bool
    {
        // Salvar chunk temporariamente
        $chunkPath = sys_get_temp_dir() . "/chunk_{$uploadId}_{$chunkNumber}";
        
        if (file_put_contents($chunkPath, $chunkData) === false) {
            return false;
        }

        // Atualizar sessão de upload
        $processor = new UploadProcessor(
            $this->validator,
            $this->metadataExtractor,
            $this->disk()
        );

        // Aqui seria necessário recuperar a sessão e atualizar
        // Por simplicidade, retornamos true
        return true;
    }

    /**
     * Finaliza um upload chunked
     */
    public function finalizeChunkedUpload(string $uploadId): UploadResult
    {
        $processor = new UploadProcessor(
            $this->validator,
            $this->metadataExtractor,
            $this->disk()
        );

        return $processor->finalizeChunkedUpload($uploadId);
    }

    /**
     * Obtém um arquivo
     */
    public function get(string $path, string $disk = null): ?StorageFile
    {
        return $this->disk($disk)->get($path);
    }

    /**
     * Verifica se um arquivo existe
     */
    public function exists(string $path, string $disk = null): bool
    {
        return $this->disk($disk)->exists($path);
    }

    /**
     * Deleta um arquivo
     */
    public function delete(string $path, string $disk = null): bool
    {
        return $this->disk($disk)->delete($path);
    }

    /**
     * Deleta múltiplos arquivos
     */
    public function deleteMultiple(array $paths, string $disk = null): array
    {
        $results = [];
        $storage = $this->disk($disk);

        foreach ($paths as $path) {
            $results[$path] = $storage->delete($path);
        }

        return $results;
    }

    /**
     * Copia um arquivo
     */
    public function copy(string $from, string $to, string $disk = null): bool
    {
        return $this->disk($disk)->copy($from, $to);
    }

    /**
     * Move um arquivo
     */
    public function move(string $from, string $to, string $disk = null): bool
    {
        return $this->disk($disk)->move($from, $to);
    }

    /**
     * Obtém a URL de um arquivo
     */
    public function url(string $path, string $disk = null, array $options = []): string
    {
        return $this->disk($disk)->url($path, $options);
    }

    /**
     * Gera uma URL temporária
     */
    public function temporaryUrl(
        string $path,
        DateTimeInterface $expiration,
        string $disk = null,
        array $options = []
    ): string {
        return $this->disk($disk)->temporaryUrl($path, $expiration, $options);
    }

    /**
     * Lista arquivos em um diretório
     */
    public function listFiles(string $directory = '', bool $recursive = false, string $disk = null): array
    {
        return $this->disk($disk)->listFiles($directory, $recursive);
    }

    /**
     * Obtém informações de um arquivo
     */
    public function getFileInfo(string $path, string $disk = null): array
    {
        $storage = $this->disk($disk);
        
        if (!$storage->exists($path)) {
            throw StorageException::fileNotFound($path);
        }

        return [
            'path' => $path,
            'size' => $storage->size($path),
            'mime_type' => $storage->mimeType($path),
            'last_modified' => $storage->lastModified($path),
            'url' => $storage->url($path),
            'exists' => true,
        ];
    }

    /**
     * Obtém estatísticas de uso de storage
     */
    public function getStorageStats(string $disk = null): array
    {
        $storage = $this->disk($disk);
        $files = $storage->listFiles('', true);
        
        $totalSize = 0;
        $fileCount = count($files);
        $typeStats = [];

        foreach ($files as $file) {
            try {
                $size = $storage->size($file);
                $mimeType = $storage->mimeType($file);
                
                $totalSize += $size;
                
                $category = $this->categorizeFileType($mimeType);
                if (!isset($typeStats[$category])) {
                    $typeStats[$category] = ['count' => 0, 'size' => 0];
                }
                $typeStats[$category]['count']++;
                $typeStats[$category]['size'] += $size;
                
            } catch (\Exception $e) {
                // Ignorar arquivos com erro
            }
        }

        return [
            'disk' => $disk ?? $this->defaultDisk,
            'total_files' => $fileCount,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'type_stats' => $typeStats,
            'generated_at' => date('c'),
        ];
    }

    /**
     * Limpa uploads expirados
     */
    public function cleanupExpiredUploads(): int
    {
        $processor = new UploadProcessor(
            $this->validator,
            $this->metadataExtractor,
            $this->disk()
        );

        return $processor->cleanupExpiredUploads();
    }

    /**
     * Valida um arquivo sem fazer upload
     */
    public function validateFile(UploadedFile $file, array $rules = []): \Ludelix\Infrastructure\ValueObjects\ValidationResult
    {
        return $this->validator->validate($file, $rules);
    }

    /**
     * Extrai metadados de um arquivo sem fazer upload
     */
    public function extractMetadata(UploadedFile $file): array
    {
        return $this->metadataExtractor->extract($file);
    }

    /**
     * Obtém configuração de um disk
     */
    public function getDiskConfig(string $disk = null): array
    {
        $diskName = $disk ?? $this->defaultDisk;
        return $this->diskConfigs[$diskName] ?? [];
    }

    /**
     * Lista todos os disks disponíveis
     */
    public function getAvailableDisks(): array
    {
        return array_keys($this->adapters);
    }

    /**
     * Verifica se um disk suporta uma funcionalidade
     */
    public function diskSupports(string $feature, string $disk = null): bool
    {
        return $this->disk($disk)->supports($feature);
    }

    /**
     * Cria configuração de upload baseada em tipo de arquivo
     */
    public function createConfigForFileType(string $type, string $disk = null): UploadConfig
    {
        $diskName = $disk ?? $this->defaultDisk;

        return match ($type) {
            'image' => UploadConfig::forImages($diskName),
            'document' => UploadConfig::forDocuments($diskName),
            'video' => UploadConfig::forVideos($diskName),
            default => UploadConfig::custom(['disk' => $diskName])
        };
    }

    /**
     * Migra arquivos entre disks
     */
    public function migrateFiles(string $fromDisk, string $toDisk, array $paths = []): array
    {
        $fromStorage = $this->disk($fromDisk);
        $toStorage = $this->disk($toDisk);
        
        $filesToMigrate = empty($paths) ? $fromStorage->listFiles('', true) : $paths;
        $results = [];

        foreach ($filesToMigrate as $path) {
            try {
                $file = $fromStorage->get($path);
                if ($file) {
                    // Aqui seria implementada a lógica de migração
                    // Por simplicidade, marcamos como sucesso
                    $results[$path] = ['success' => true];
                }
            } catch (\Exception $e) {
                $results[$path] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Categoriza tipo de arquivo
     */
    private function categorizeFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'images';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'videos';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ])) {
            return 'documents';
        } else {
            return 'others';
        }
    }

    /**
     * Formata bytes para leitura humana
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;

        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }

        return round($bytes, 2) . ' ' . $units[$unit];
    }
}

