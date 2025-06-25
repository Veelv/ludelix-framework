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
 * Adaptador para Digital Ocean Spaces
 * 
 * Implementa o armazenamento de arquivos no Digital Ocean Spaces,
 * que é compatível com S3 mas com endpoint e CDN específicos.
 */
class DigitalOceanAdapter implements StorageInterface
{
    private string $bucket;
    private string $region;
    private string $accessKey;
    private string $secretKey;
    private string $endpoint;
    private ?string $cdnUrl;
    private ?string $baseUrl;
    private $spacesClient;

    public function __construct(array $config)
    {
        $this->bucket = $config['bucket'] ?? throw new StorageException('Bucket Digital Ocean não configurado');
        $this->region = $config['region'] ?? throw new StorageException('Região Digital Ocean não configurada');
        $this->accessKey = $config['key'] ?? throw new StorageException('Access Key Digital Ocean não configurada');
        $this->secretKey = $config['secret'] ?? throw new StorageException('Secret Key Digital Ocean não configurada');
        $this->endpoint = $config['endpoint'] ?? "https://{$this->region}.digitaloceanspaces.com";
        $this->cdnUrl = $config['cdn_url'] ?? null;
        $this->baseUrl = $config['url'] ?? null;

        $this->initializeSpacesClient();
    }

    public function store(UploadedFile $file, string $path, array $options = []): StorageResult
    {
        try {
            $key = ltrim($path, '/');
            $metadata = $this->extractUploadMetadata($file);
            
            // Preparar parâmetros do upload
            $uploadParams = [
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => fopen($file->getPathname(), 'r'),
                'ContentType' => $file->getMimeType(),
                'Metadata' => $metadata,
                'ACL' => $options['acl'] ?? 'public-read', // Digital Ocean padrão
            ];

            // Realizar upload
            $result = $this->spacesClient->putObject($uploadParams);

            // Gerar URL (preferir CDN se disponível)
            $url = $this->generateUrl($path, $options['use_cdn'] ?? true);

            // Gerar hash se solicitado
            $hash = null;
            if ($options['generate_hash'] ?? true) {
                $hash = hash_file($options['hash_algorithm'] ?? 'sha256', $file->getPathname());
            }

            return StorageResult::success(
                path: $path,
                url: $url,
                size: $file->getSize(),
                mimeType: $file->getMimeType(),
                metadata: array_merge($metadata, [
                    'etag' => trim($result['ETag'], '"'),
                    'provider' => 'digitalocean',
                    'region' => $this->region,
                ]),
                hash: $hash,
                originalName: $file->getClientOriginalName()
            );

        } catch (\Exception $e) {
            return StorageResult::failure(
                error: "Erro no upload Digital Ocean: " . $e->getMessage(),
                path: $path
            );
        }
    }

    public function get(string $path): ?StorageFile
    {
        try {
            $key = ltrim($path, '/');
            
            // Verificar se objeto existe
            $headResult = $this->spacesClient->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $size = (int) $headResult['ContentLength'];
            $mimeType = $headResult['ContentType'] ?? 'application/octet-stream';
            $lastModified = new DateTimeImmutable($headResult['LastModified']);
            $url = $this->generateUrl($path, true);

            // Extrair metadados
            $metadata = $headResult['Metadata'] ?? [];
            $metadata['etag'] = trim($headResult['ETag'], '"');
            $metadata['provider'] = 'digitalocean';

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
            throw new StorageException("Erro ao obter arquivo Digital Ocean: " . $e->getMessage());
        }
    }

    public function delete(string $path): bool
    {
        try {
            $key = ltrim($path, '/');
            
            $this->spacesClient->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            $key = ltrim($path, '/');
            
            $this->spacesClient->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function url(string $path, array $options = []): string
    {
        $useCdn = $options['use_cdn'] ?? true;
        return $this->generateUrl($path, $useCdn);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        try {
            $key = ltrim($path, '/');
            $expires = $expiration->getTimestamp() - time();

            $command = $this->spacesClient->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $request = $this->spacesClient->createPresignedRequest($command, "+{$expires} seconds");
            
            return (string) $request->getUri();

        } catch (\Exception $e) {
            throw new StorageException("Erro ao gerar URL temporária Digital Ocean: " . $e->getMessage());
        }
    }

    public function copy(string $from, string $to): bool
    {
        try {
            $fromKey = ltrim($from, '/');
            $toKey = ltrim($to, '/');

            $this->spacesClient->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $toKey,
                'CopySource' => $this->bucket . '/' . $fromKey,
                'ACL' => 'public-read',
            ]);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function move(string $from, string $to): bool
    {
        if ($this->copy($from, $to)) {
            return $this->delete($from);
        }

        return false;
    }

    public function size(string $path): int
    {
        try {
            $key = ltrim($path, '/');
            
            $result = $this->spacesClient->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return (int) $result['ContentLength'];

        } catch (\Exception $e) {
            throw new StorageException("Erro ao obter tamanho do arquivo Digital Ocean: " . $e->getMessage());
        }
    }

    public function lastModified(string $path): DateTimeInterface
    {
        try {
            $key = ltrim($path, '/');
            
            $result = $this->spacesClient->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return new DateTimeImmutable($result['LastModified']);

        } catch (\Exception $e) {
            throw new StorageException("Erro ao obter data de modificação Digital Ocean: " . $e->getMessage());
        }
    }

    public function mimeType(string $path): string
    {
        try {
            $key = ltrim($path, '/');
            
            $result = $this->spacesClient->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return $result['ContentType'] ?? 'application/octet-stream';

        } catch (\Exception $e) {
            throw new StorageException("Erro ao obter tipo MIME Digital Ocean: " . $e->getMessage());
        }
    }

    public function listFiles(string $directory = '', bool $recursive = false): array
    {
        try {
            $prefix = ltrim($directory, '/');
            if (!empty($prefix) && !str_ends_with($prefix, '/')) {
                $prefix .= '/';
            }

            $params = [
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ];

            if (!$recursive) {
                $params['Delimiter'] = '/';
            }

            $files = [];
            $paginator = $this->spacesClient->getPaginator('ListObjectsV2', $params);

            foreach ($paginator as $page) {
                if (isset($page['Contents'])) {
                    foreach ($page['Contents'] as $object) {
                        $files[] = $object['Key'];
                    }
                }
            }

            return $files;

        } catch (\Exception $e) {
            throw new StorageException("Erro ao listar arquivos Digital Ocean: " . $e->getMessage());
        }
    }

    public function getConfig(): array
    {
        return [
            'driver' => 'digitalocean',
            'bucket' => $this->bucket,
            'region' => $this->region,
            'endpoint' => $this->endpoint,
            'cdn_url' => $this->cdnUrl,
            'url' => $this->baseUrl,
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
            'cdn',
            'public_access',
        ];

        return in_array($feature, $supportedFeatures);
    }

    /**
     * Obtém estatísticas específicas do Digital Ocean Spaces
     */
    public function getSpacesStats(): array
    {
        try {
            $files = $this->listFiles('', true);
            $totalSize = 0;
            $fileCount = count($files);

            foreach ($files as $file) {
                try {
                    $totalSize += $this->size($file);
                } catch (\Exception $e) {
                    // Ignorar arquivos com erro
                }
            }

            return [
                'space_name' => $this->bucket,
                'region' => $this->region,
                'total_files' => $fileCount,
                'total_size' => $totalSize,
                'total_size_human' => $this->formatBytes($totalSize),
                'cdn_enabled' => !empty($this->cdnUrl),
                'cdn_url' => $this->cdnUrl,
                'endpoint' => $this->endpoint,
            ];

        } catch (\Exception $e) {
            throw new StorageException("Erro ao obter estatísticas Digital Ocean: " . $e->getMessage());
        }
    }

    /**
     * Configura CORS para o Space
     */
    public function configureCORS(array $corsRules): bool
    {
        try {
            // Implementação simplificada
            // Na prática, usaria a API do Digital Ocean para configurar CORS
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Inicializa o cliente Spaces (compatível com S3)
     */
    private function initializeSpacesClient(): void
    {
        // Simulação da inicialização do cliente Digital Ocean Spaces
        // Na implementação real, seria usado um cliente S3-compatível
        $this->spacesClient = new class($this->accessKey, $this->secretKey, $this->endpoint) {
            public function __construct(
                private string $accessKey,
                private string $secretKey,
                private string $endpoint
            ) {}

            public function putObject(array $params): array
            {
                // Simulação do upload
                return [
                    'ETag' => '"' . md5(uniqid()) . '"',
                ];
            }

            public function headObject(array $params): array
            {
                // Simulação da verificação de objeto
                return [
                    'ContentLength' => 1024,
                    'ContentType' => 'application/octet-stream',
                    'LastModified' => date('c'),
                    'ETag' => '"' . md5($params['Key']) . '"',
                    'Metadata' => [],
                ];
            }

            public function deleteObject(array $params): array
            {
                return [];
            }

            public function copyObject(array $params): array
            {
                return [];
            }

            public function getCommand(string $name, array $params): object
            {
                return new class($name, $params) {
                    public function __construct(public string $name, public array $params) {}
                };
            }

            public function createPresignedRequest(object $command, string $expires): object
            {
                return new class($command, $expires) {
                    public function __construct(private object $command, private string $expires) {}
                    public function getUri(): string {
                        return 'https://example.nyc3.digitaloceanspaces.com/signed-url';
                    }
                };
            }

            public function getPaginator(string $name, array $params): \Generator
            {
                yield [
                    'Contents' => [
                        ['Key' => 'example-file.txt'],
                    ]
                ];
            }
        };
    }

    /**
     * Gera URL para o arquivo (CDN ou endpoint direto)
     */
    private function generateUrl(string $path, bool $useCdn = true): string
    {
        $key = ltrim($path, '/');

        // Usar CDN se disponível e solicitado
        if ($useCdn && $this->cdnUrl) {
            return rtrim($this->cdnUrl, '/') . '/' . $key;
        }

        // Usar URL base personalizada se configurada
        if ($this->baseUrl) {
            return rtrim($this->baseUrl, '/') . '/' . $key;
        }

        // URL padrão do Digital Ocean Spaces
        return "https://{$this->bucket}.{$this->region}.digitaloceanspaces.com/{$key}";
    }

    /**
     * Extrai metadados para upload
     */
    private function extractUploadMetadata(UploadedFile $file): array
    {
        return [
            'original-name' => $file->getClientOriginalName(),
            'original-extension' => $file->getClientOriginalExtension(),
            'uploaded-at' => date('c'),
            'file-size' => (string) $file->getSize(),
            'provider' => 'digitalocean',
        ];
    }

    /**
     * Verifica se o erro é de arquivo não encontrado
     */
    private function isNotFoundError(\Exception $e): bool
    {
        return str_contains($e->getMessage(), 'NoSuchKey') || 
               str_contains($e->getMessage(), '404') ||
               str_contains($e->getMessage(), 'Not Found');
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

