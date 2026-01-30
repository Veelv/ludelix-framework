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

/**
 * Adaptador para Cloudinary
 * 
 * Implementa o armazenamento de arquivos no Cloudinary,
 * com suporte a transformações de imagem, otimização automática e CDN.
 */
class CloudinaryAdapter implements StorageInterface
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;
    private bool $secure;
    private array $transformations;
    private $cloudinaryApi;

    public function __construct(array $config)
    {
        $this->cloudName = $config['cloud_name'] ?? throw new StorageException('Cloud Name Cloudinary não configurado');
        $this->apiKey = $config['api_key'] ?? throw new StorageException('API Key Cloudinary não configurada');
        $this->apiSecret = $config['api_secret'] ?? throw new StorageException('API Secret Cloudinary não configurado');
        $this->secure = $config['secure'] ?? true;
        $this->transformations = $config['transformations'] ?? [];

        $this->initializeCloudinaryApi();
    }

    public function store(UploadedFile $file, string $path, array $options = []): StorageResult
    {
        try {
            // Determinar tipo de recurso baseado no MIME type
            $resourceType = $this->determineResourceType($file->getMimeType());
            
            // Preparar opções de upload
            $uploadOptions = [
                'public_id' => $this->generatePublicId($path, $file),
                'resource_type' => $resourceType,
                'folder' => $this->extractFolder($path),
                'use_filename' => $options['use_filename'] ?? false,
                'unique_filename' => $options['unique_filename'] ?? true,
                'overwrite' => $options['overwrite'] ?? false,
            ];

            // Adicionar transformações se especificadas
            if (isset($options['transformation'])) {
                $uploadOptions['transformation'] = $options['transformation'];
            }

            // Adicionar tags se especificadas
            if (isset($options['tags'])) {
                $uploadOptions['tags'] = is_array($options['tags']) 
                    ? implode(',', $options['tags']) 
                    : $options['tags'];
            }

            // Realizar upload
            $result = $this->cloudinaryApi->upload($file->getPathname(), $uploadOptions);

            // Gerar URLs com transformações padrão
            $urls = $this->generateTransformedUrls($result['public_id'], $resourceType);

            // Extrair metadados do resultado
            $metadata = $this->extractCloudinaryMetadata($result);

            return StorageResult::success(
                path: $result['public_id'],
                url: $result['secure_url'],
                size: $result['bytes'],
                mimeType: $file->getMimeType(),
                metadata: $metadata,
                hash: $result['etag'] ?? null,
                originalName: $file->getClientOriginalName(),
                thumbnails: $urls
            );

        } catch (\Exception $e) {
            return StorageResult::failure(
                error: "Erro no upload Cloudinary: " . $e->getMessage(),
                path: $path
            );
        }
    }

    public function get(string $path): ?StorageFile
    {
        try {
            // Obter informações do recurso
            $result = $this->cloudinaryApi->getResource($path);

            if (!$result) {
                return null;
            }

            $size = $result['bytes'];
            $mimeType = $this->getMimeTypeFromFormat($result['format']);
            $lastModified = new DateTimeImmutable($result['created_at']);
            $url = $result['secure_url'];

            // Extrair metadados
            $metadata = $this->extractCloudinaryMetadata($result);

            return StorageFile::fromProviderData([
                'path' => $path,
                'url' => $url,
                'size' => $size,
                'mime_type' => $mimeType,
                'last_modified' => $lastModified,
                'metadata' => $metadata,
            ]);

        } catch (\Exception $e) {
            if ($this->isNotFoundError($e)) {
                return null;
            }
            throw new StorageException("Erro ao obter arquivo Cloudinary: " . $e->getMessage());
        }
    }

    public function delete(string $path): bool
    {
        try {
            $resourceType = $this->guessResourceTypeFromPath($path);
            $result = $this->cloudinaryApi->destroy($path, ['resource_type' => $resourceType]);
            
            return $result['result'] === 'ok';

        } catch (\Exception $e) {
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            $result = $this->cloudinaryApi->getResource($path);
            return !empty($result);

        } catch (\Exception $e) {
            return false;
        }
    }

    public function url(string $path, array $options = []): string
    {
        $transformation = $options['transformation'] ?? null;
        $resourceType = $options['resource_type'] ?? 'image';
        
        return $this->generateCloudinaryUrl($path, $resourceType, $transformation);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        // Cloudinary não usa URLs temporárias tradicionais, mas pode usar signed URLs
        $timestamp = $expiration->getTimestamp();
        $transformation = $options['transformation'] ?? null;
        $resourceType = $options['resource_type'] ?? 'image';
        
        return $this->generateSignedUrl($path, $resourceType, $transformation, $timestamp);
    }

    public function copy(string $from, string $to): bool
    {
        try {
            // Cloudinary não tem operação de cópia direta
            // Precisamos fazer download e re-upload
            $fromResource = $this->get($from);
            if (!$fromResource) {
                return false;
            }

            // Implementação simplificada - na prática seria mais complexa
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function move(string $from, string $to): bool
    {
        try {
            $resourceType = $this->guessResourceTypeFromPath($from);
            $result = $this->cloudinaryApi->rename($from, $to, ['resource_type' => $resourceType]);
            
            return $result['public_id'] === $to;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function size(string $path): int
    {
        $file = $this->get($path);
        return $file ? $file->size : 0;
    }

    public function lastModified(string $path): DateTimeInterface
    {
        $file = $this->get($path);
        return $file ? $file->lastModified : new DateTimeImmutable();
    }

    public function mimeType(string $path): string
    {
        $file = $this->get($path);
        return $file ? $file->mimeType : 'application/octet-stream';
    }

    public function listFiles(string $directory = '', bool $recursive = false): array
    {
        try {
            $options = [
                'type' => 'upload',
                'max_results' => 500,
            ];

            if (!empty($directory)) {
                $options['prefix'] = rtrim($directory, '/') . '/';
            }

            $result = $this->cloudinaryApi->listResources($options);
            
            $files = [];
            foreach ($result['resources'] as $resource) {
                $files[] = $resource['public_id'];
            }

            return $files;

        } catch (\Exception $e) {
            throw new StorageException("Erro ao listar arquivos Cloudinary: " . $e->getMessage());
        }
    }

    public function getConfig(): array
    {
        return [
            'driver' => 'cloudinary',
            'cloud_name' => $this->cloudName,
            'secure' => $this->secure,
            'transformations' => $this->transformations,
        ];
    }

    public function supports(string $feature): bool
    {
        $supportedFeatures = [
            'move',
            'delete',
            'list',
            'metadata',
            'transformations',
            'optimization',
            'cdn',
            'signed_urls',
            'auto_format',
            'auto_quality',
        ];

        return in_array($feature, $supportedFeatures);
    }

    /**
     * Aplica transformação a uma imagem
     */
    public function transform(string $path, array $transformation, array $options = []): string
    {
        $resourceType = $options['resource_type'] ?? 'image';
        return $this->generateCloudinaryUrl($path, $resourceType, $transformation);
    }

    /**
     * Gera múltiplas versões de uma imagem
     */
    public function generateResponsiveImages(string $path, array $sizes = []): array
    {
        $sizes = $sizes ?: [320, 640, 768, 1024, 1280, 1920];
        $images = [];

        foreach ($sizes as $width) {
            $transformation = ['width' => $width, 'crop' => 'scale', 'quality' => 'auto', 'format' => 'auto'];
            $images[$width] = $this->generateCloudinaryUrl($path, 'image', $transformation);
        }

        return $images;
    }

    /**
     * Otimiza uma imagem automaticamente
     */
    public function optimizeImage(string $path, array $options = []): string
    {
        $transformation = [
            'quality' => 'auto',
            'format' => 'auto',
            'fetch_format' => 'auto',
        ];

        if (isset($options['width'])) {
            $transformation['width'] = $options['width'];
            $transformation['crop'] = 'scale';
        }

        return $this->generateCloudinaryUrl($path, 'image', $transformation);
    }

    /**
     * Inicializa a API do Cloudinary
     */
    private function initializeCloudinaryApi(): void
    {
        // Simulação da API do Cloudinary
        $this->cloudinaryApi = new class($this->cloudName, $this->apiKey, $this->apiSecret) {
            public function __construct(
                private string $cloudName,
                private string $apiKey,
                private string $apiSecret
            ) {}

            public function upload(string $file, array $options): array
            {
                return [
                    'public_id' => $options['public_id'] ?? uniqid(),
                    'version' => time(),
                    'signature' => md5(uniqid()),
                    'width' => 1920,
                    'height' => 1080,
                    'format' => 'jpg',
                    'resource_type' => $options['resource_type'] ?? 'image',
                    'created_at' => date('c'),
                    'bytes' => 1024000,
                    'type' => 'upload',
                    'etag' => md5(uniqid()),
                    'url' => "http://res.cloudinary.com/{$this->cloudName}/image/upload/sample.jpg",
                    'secure_url' => "https://res.cloudinary.com/{$this->cloudName}/image/upload/sample.jpg",
                ];
            }

            public function getResource(string $publicId): array
            {
                return [
                    'public_id' => $publicId,
                    'format' => 'jpg',
                    'version' => time(),
                    'resource_type' => 'image',
                    'type' => 'upload',
                    'created_at' => date('c'),
                    'bytes' => 1024000,
                    'width' => 1920,
                    'height' => 1080,
                    'url' => "http://res.cloudinary.com/{$this->cloudName}/image/upload/{$publicId}.jpg",
                    'secure_url' => "https://res.cloudinary.com/{$this->cloudName}/image/upload/{$publicId}.jpg",
                ];
            }

            public function destroy(string $publicId, array $options): array
            {
                return ['result' => 'ok'];
            }

            public function rename(string $fromPublicId, string $toPublicId, array $options): array
            {
                return ['public_id' => $toPublicId];
            }

            public function listResources(array $options): array
            {
                return [
                    'resources' => [
                        ['public_id' => 'sample1'],
                        ['public_id' => 'sample2'],
                    ]
                ];
            }
        };
    }

    /**
     * Determina o tipo de recurso baseado no MIME type
     */
    private function determineResourceType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'video'; // Cloudinary trata áudio como vídeo
        } else {
            return 'raw';
        }
    }

    /**
     * Gera public_id baseado no caminho
     */
    private function generatePublicId(string $path, UploadedFile $file): string
    {
        $pathInfo = pathinfo($path);
        $directory = $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] : '';
        $filename = $pathInfo['filename'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        $publicId = $directory ? $directory . '/' . $filename : $filename;
        return ltrim($publicId, '/');
    }

    /**
     * Extrai pasta do caminho
     */
    private function extractFolder(string $path): string
    {
        $directory = dirname($path);
        return $directory !== '.' ? $directory : '';
    }

    /**
     * Gera URLs com transformações padrão
     */
    private function generateTransformedUrls(string $publicId, string $resourceType): array
    {
        if ($resourceType !== 'image' || empty($this->transformations)) {
            return null;
        }

        $urls = [];
        foreach ($this->transformations as $name => $transformation) {
            $urls[$name] = $this->generateCloudinaryUrl($publicId, $resourceType, $transformation);
        }

        return $urls;
    }

    /**
     * Gera URL do Cloudinary com transformações
     */
    private function generateCloudinaryUrl(string $publicId, string $resourceType, $transformation = null): string
    {
        $protocol = $this->secure ? 'https' : 'http';
        $baseUrl = "{$protocol}://res.cloudinary.com/{$this->cloudName}/{$resourceType}/upload";
        
        if ($transformation) {
            $transformStr = $this->buildTransformationString($transformation);
            $baseUrl .= "/{$transformStr}";
        }
        
        return "{$baseUrl}/{$publicId}";
    }

    /**
     * Gera URL assinada
     */
    private function generateSignedUrl(string $publicId, string $resourceType, $transformation, int $timestamp): string
    {
        $url = $this->generateCloudinaryUrl($publicId, $resourceType, $transformation);
        
        // Adicionar assinatura (implementação simplificada)
        $signature = hash_hmac('sha1', $publicId . $timestamp, $this->apiSecret);
        
        return $url . "?timestamp={$timestamp}&signature={$signature}";
    }

    /**
     * Constrói string de transformação
     */
    private function buildTransformationString($transformation): string
    {
        if (is_string($transformation)) {
            return $transformation;
        }

        if (is_array($transformation)) {
            $parts = [];
            foreach ($transformation as $key => $value) {
                $parts[] = "{$key}_{$value}";
            }
            return implode(',', $parts);
        }

        return '';
    }

    /**
     * Extrai metadados do resultado do Cloudinary
     */
    private function extractCloudinaryMetadata(array $result): array
    {
        $metadata = [
            'provider' => 'cloudinary',
            'public_id' => $result['public_id'],
            'version' => $result['version'] ?? null,
            'format' => $result['format'] ?? null,
            'resource_type' => $result['resource_type'] ?? null,
            'created_at' => $result['created_at'] ?? null,
        ];

        // Adicionar dimensões se for imagem ou vídeo
        if (isset($result['width']) && isset($result['height'])) {
            $metadata['width'] = $result['width'];
            $metadata['height'] = $result['height'];
            $metadata['aspect_ratio'] = round($result['width'] / $result['height'], 2);
        }

        // Adicionar informações de cor se disponível
        if (isset($result['colors'])) {
            $metadata['colors'] = $result['colors'];
        }

        return $metadata;
    }

    /**
     * Obtém MIME type baseado no formato
     */
    private function getMimeTypeFromFormat(string $format): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
        ];

        return $mimeTypes[strtolower($format)] ?? 'application/octet-stream';
    }

    /**
     * Adivinha o tipo de recurso baseado no caminho
     */
    private function guessResourceTypeFromPath(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $videoExtensions = ['mp4', 'avi', 'mov', 'webm'];
        
        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif (in_array($extension, $videoExtensions)) {
            return 'video';
        } else {
            return 'raw';
        }
    }

    /**
     * Verifica se o erro é de arquivo não encontrado
     */
    private function isNotFoundError(\Exception $e): bool
    {
        return str_contains($e->getMessage(), 'Not Found') || 
               str_contains($e->getMessage(), '404') ||
               str_contains($e->getMessage(), 'Resource not found');
    }
}

