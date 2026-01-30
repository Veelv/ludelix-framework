<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Adapters;

use Ludelix\Infrastructure\Contracts\StorageInterface;
use Ludelix\Infrastructure\ValueObjects\StorageResult;
use Ludelix\Infrastructure\ValueObjects\StorageFile;
use Ludelix\Infrastructure\Exceptions\StorageException;
use Ludelix\PRT\UploadedFile;
use DateTimeInterface;
use DateTimeImmutable;
use RuntimeException;

/**
 * Adaptador para armazenamento local no sistema de arquivos
 * 
 * Implementa o armazenamento de arquivos no sistema de arquivos local,
 * com suporte a diferentes visibilidades e configurações de permissão.
 */
class LocalAdapter implements StorageInterface
{
    private string $root;
    private string $baseUrl;
    private string $visibility;
    private array $permissions;

    public function __construct(array $config)
    {
        $this->root = rtrim($config['root'] ?? cubby_path('app/uploads'), '/');
        $this->baseUrl = rtrim($config['url'] ?? '/cubby', '/');
        $this->visibility = $config['visibility'] ?? 'public';
        $this->permissions = $config['permissions'] ?? [
            'file' => ['public' => 0644, 'private' => 0600],
            'dir' => ['public' => 0755, 'private' => 0700],
        ];

        $this->ensureDirectoryExists($this->root);
    }

    public function store(UploadedFile $file, string $path, array $options = []): StorageResult
    {
        try {
            $fullPath = $this->getFullPath($path);
            $directory = dirname($fullPath);

            // Criar diretório se não existir
            $this->ensureDirectoryExists($directory);

            // Mover arquivo para destino
            if (!$file->move($directory, basename($fullPath))) {
                throw new StorageException("Falha ao mover arquivo para: {$fullPath}");
            }

            // Definir permissões
            $this->setPermissions($fullPath, 'file');

            // Obter informações do arquivo
            $size = filesize($fullPath);
            $mimeType = $this->getMimeType($fullPath);
            $url = $this->generateUrl($path);

            // Extrair metadados básicos
            $metadata = $this->extractBasicMetadata($fullPath, $file);

            // Gerar hash se solicitado
            $hash = null;
            if ($options['generate_hash'] ?? true) {
                $hash = hash_file($options['hash_algorithm'] ?? 'sha256', $fullPath);
            }

            return StorageResult::success(
                path: $path,
                url: $url,
                size: $size,
                mimeType: $mimeType,
                metadata: $metadata,
                hash: $hash,
                originalName: $file->getClientOriginalName()
            );

        } catch (\Exception $e) {
            return StorageResult::failure(
                error: "Erro ao armazenar arquivo: " . $e->getMessage(),
                path: $path
            );
        }
    }

    public function get(string $path): ?StorageFile
    {
        $fullPath = $this->getFullPath($path);

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return null;
        }

        try {
            $size = filesize($fullPath);
            $mimeType = $this->getMimeType($fullPath);
            $lastModified = new DateTimeImmutable('@' . filemtime($fullPath));
            $url = $this->generateUrl($path);

            // Extrair metadados básicos
            $metadata = $this->extractFileMetadata($fullPath);

            return StorageFile::fromProviderData([
                'path' => $path,
                'url' => $url,
                'size' => $size,
                'mime_type' => $mimeType,
                'last_modified' => $lastModified,
                'metadata' => $metadata,
            ]);

        } catch (\Exception $e) {
            throw new StorageException("Erro ao obter arquivo: " . $e->getMessage());
        }
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->getFullPath($path);

        if (!file_exists($fullPath)) {
            return true; // Arquivo já não existe
        }

        return unlink($fullPath);
    }

    public function exists(string $path): bool
    {
        return file_exists($this->getFullPath($path));
    }

    public function url(string $path, array $options = []): string
    {
        return $this->generateUrl($path);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        // Para storage local, geramos uma URL assinada simples
        $timestamp = $expiration->getTimestamp();
        $signature = hash_hmac('sha256', $path . $timestamp, config('app.key', 'default-key'));
        
        return $this->generateUrl($path) . '?expires=' . $timestamp . '&signature=' . $signature;
    }

    public function copy(string $from, string $to): bool
    {
        $fromPath = $this->getFullPath($from);
        $toPath = $this->getFullPath($to);

        if (!file_exists($fromPath)) {
            return false;
        }

        $this->ensureDirectoryExists(dirname($toPath));
        
        if (copy($fromPath, $toPath)) {
            $this->setPermissions($toPath, 'file');
            return true;
        }

        return false;
    }

    public function move(string $from, string $to): bool
    {
        $fromPath = $this->getFullPath($from);
        $toPath = $this->getFullPath($to);

        if (!file_exists($fromPath)) {
            return false;
        }

        $this->ensureDirectoryExists(dirname($toPath));
        
        if (rename($fromPath, $toPath)) {
            $this->setPermissions($toPath, 'file');
            return true;
        }

        return false;
    }

    public function size(string $path): int
    {
        $fullPath = $this->getFullPath($path);
        
        if (!file_exists($fullPath)) {
            throw new StorageException("Arquivo não encontrado: {$path}");
        }

        return filesize($fullPath);
    }

    public function lastModified(string $path): DateTimeInterface
    {
        $fullPath = $this->getFullPath($path);
        
        if (!file_exists($fullPath)) {
            throw new StorageException("Arquivo não encontrado: {$path}");
        }

        return new DateTimeImmutable('@' . filemtime($fullPath));
    }

    public function mimeType(string $path): string
    {
        $fullPath = $this->getFullPath($path);
        
        if (!file_exists($fullPath)) {
            throw new StorageException("Arquivo não encontrado: {$path}");
        }

        return $this->getMimeType($fullPath);
    }

    public function listFiles(string $directory = '', bool $recursive = false): array
    {
        $fullPath = $this->getFullPath($directory);
        
        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];
        $pattern = $recursive ? '**/*' : '*';
        
        foreach (glob($fullPath . '/' . $pattern, GLOB_BRACE) as $file) {
            if (is_file($file)) {
                $relativePath = str_replace($this->root . '/', '', $file);
                $files[] = $relativePath;
            }
        }

        return $files;
    }

    public function getConfig(): array
    {
        return [
            'driver' => 'local',
            'root' => $this->root,
            'url' => $this->baseUrl,
            'visibility' => $this->visibility,
            'permissions' => $this->permissions,
        ];
    }

    public function supports(string $feature): bool
    {
        $supportedFeatures = [
            'copy',
            'move',
            'delete',
            'list',
            'metadata',
            'temporary_urls',
        ];

        return in_array($feature, $supportedFeatures);
    }

    /**
     * Obtém o caminho completo no sistema de arquivos
     */
    private function getFullPath(string $path): string
    {
        return $this->root . '/' . ltrim($path, '/');
    }

    /**
     * Gera URL pública para o arquivo
     */
    private function generateUrl(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Garante que um diretório existe
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, $this->permissions['dir'][$this->visibility], true)) {
                throw new StorageException("Falha ao criar diretório: {$directory}");
            }
        }
    }

    /**
     * Define permissões para arquivo ou diretório
     */
    private function setPermissions(string $path, string $type): void
    {
        $permission = $this->permissions[$type][$this->visibility] ?? 0644;
        chmod($path, $permission);
    }

    /**
     * Obtém o tipo MIME de um arquivo
     */
    private function getMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mimeType ?: 'application/octet-stream';
    }

    /**
     * Extrai metadados básicos durante o upload
     */
    private function extractBasicMetadata(string $fullPath, UploadedFile $file): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'original_extension' => $file->getClientOriginalExtension(),
            'uploaded_at' => (new DateTimeImmutable())->format('c'),
        ];

        // Adicionar metadados específicos para imagens
        if (str_starts_with($this->getMimeType($fullPath), 'image/')) {
            $imageInfo = getimagesize($fullPath);
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['type'] = $imageInfo[2];
                $metadata['bits'] = $imageInfo['bits'] ?? null;
                $metadata['channels'] = $imageInfo['channels'] ?? null;
            }
        }

        return $metadata;
    }

    /**
     * Extrai metadados de um arquivo existente
     */
    private function extractFileMetadata(string $fullPath): array
    {
        $metadata = [
            'size' => filesize($fullPath),
            'created_at' => (new DateTimeImmutable('@' . filectime($fullPath)))->format('c'),
            'modified_at' => (new DateTimeImmutable('@' . filemtime($fullPath)))->format('c'),
        ];

        // Adicionar metadados específicos para imagens
        if (str_starts_with($this->getMimeType($fullPath), 'image/')) {
            $imageInfo = getimagesize($fullPath);
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['type'] = $imageInfo[2];
                $metadata['bits'] = $imageInfo['bits'] ?? null;
                $metadata['channels'] = $imageInfo['channels'] ?? null;
            }
        }

        return $metadata;
    }
}

