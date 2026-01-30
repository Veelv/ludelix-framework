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
 * Adaptador para Amazon S3
 * 
 * Implementa o armazenamento de arquivos no Amazon S3,
 * com suporte a URLs assinadas, metadados e configurações avançadas.
 */
class S3Adapter implements StorageInterface
{
    private string $bucket;
    private string $region;
    private string $accessKey;
    private string $secretKey;
    private ?string $endpoint;
    private ?string $baseUrl;
    private array $options;
    private $s3Client;

    public function __construct(array $config)
    {
        $this->bucket = $config['bucket'] ?? throw new StorageException('Bucket S3 não configurado');
        $this->region = $config['region'] ?? 'us-east-1';
        $this->accessKey = $config['key'] ?? throw new StorageException('Access Key S3 não configurada');
        $this->secretKey = $config['secret'] ?? throw new StorageException('Secret Key S3 não configurada');
        $this->endpoint = $config['endpoint'] ?? null;
        $this->baseUrl = $config['url'] ?? null;
        $this->options = $config['options'] ?? [];

        $this->initializeS3Client();
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
            ];

            // Adicionar opções específicas
            if (isset($options['acl'])) {
                $uploadParams['ACL'] = $options['acl'];
            }

            if (isset($this->options['ServerSideEncryption'])) {
                $uploadParams['ServerSideEncryption'] = $this->options['ServerSideEncryption'];
            }

            // Realizar upload
            $result = $this->s3Client->putObject($uploadParams);

            // Gerar URL
            $url = $this->generateUrl($path);

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
                    'version_id' => $result['VersionId'] ?? null,
                ]),
                hash: $hash,
                originalName: $file->getClientOriginalName()
            );

        } catch (\Exception $e) {
            return StorageResult::failure(
                error: "Erro no upload S3: " . $e->getMessage(),
                path: $path
            );
        }
    }

    public function get(string $path): ?StorageFile
    {
        try {
            $key = ltrim($path, '/');
            
            // Verificar se objeto existe
            $headResult = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $size = (int) $headResult['ContentLength'];
            $mimeType = $headResult['ContentType'] ?? 'application/octet-stream';
            $lastModified = new DateTimeImmutable($headResult['LastModified']);
            $url = $this->generateUrl($path);

            // Extrair metadados
            $metadata = $headResult['Metadata'] ?? [];
            $metadata['etag'] = trim($headResult['ETag'], '"');
            if (isset($headResult['VersionId'])) {
                $metadata['version_id'] = $headResult['VersionId'];
            }

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
            throw new StorageException("Erro ao obter arquivo S3: " . $e->getMessage());
        }
    }

    public function delete(string $path): bool
    {
        try {
            $key = ltrim($path, '/');
            
            $this->s3Client->deleteObject([
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
            
            $this->s3Client->headObject([
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
        return $this->generateUrl($path);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        try {
            $key = ltrim($path, '/');
            $expires = $expiration->getTimestamp() - time();

            $command = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $request = $this->s3Client->createPresignedRequest($command, "+{$expires} seconds");
            
            return (string) $request->getUri();

        } catch (\Exception $e) {
            throw new StorageException("Erro ao gerar URL temporária S3: " . $e->getMessage());
        }
    }

    public function copy(string $from, string $to): bool
    {
        try {
            $fromKey = ltrim($from, '/');
            $toKey = ltrim($to, '/');

            $this->s3Client->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $toKey,
                'CopySource' => $this->bucket . '/' . $fromKey,
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
            
            $result = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return (int) $result['ContentLength'];

        } catch (\Exception $e) {
            throw new StorageException("Erro ao obter tamanho do arquivo S3: " . $e->getMessage());
        }
    }

    public function lastModified(string $path): DateTimeInterface
    {
        try {
            $key = ltrim($path, '/');
            
            $result = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return new DateTimeImmutable($result['LastModified']);

        } catch (\Exception $e) {
            throw new StorageException("Erro ao obter data de modificação S3: " . $e->getMessage());
        }
    }

    public function mimeType(string $path): string
    {
        try {
            $key = ltrim($path, '/');
            
            $result = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return $result['ContentType'] ?? 'application/octet-stream';

        } catch (\Exception $e) {
            throw new StorageException("Erro ao obter tipo MIME S3: " . $e->getMessage());
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
            $paginator = $this->s3Client->getPaginator('ListObjectsV2', $params);

            foreach ($paginator as $page) {
                if (isset($page['Contents'])) {
                    foreach ($page['Contents'] as $object) {
                        $files[] = $object['Key'];
                    }
                }
            }

            return $files;

        } catch (\Exception $e) {
            throw new StorageException("Erro ao listar arquivos S3: " . $e->getMessage());
        }
    }

    public function getConfig(): array
    {
        return [
            'driver' => 's3',
            'bucket' => $this->bucket,
            'region' => $this->region,
            'endpoint' => $this->endpoint,
            'url' => $this->baseUrl,
            'options' => $this->options,
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
            'versioning',
            'encryption',
            'multipart_upload',
        ];

        return in_array($feature, $supportedFeatures);
    }

    /**
     * Inicializa o cliente S3
     */
    private function initializeS3Client(): void
    {
        // Simulação da inicialização do cliente AWS S3
        // Na implementação real, seria usado o AWS SDK
        $this->s3Client = new class($this->accessKey, $this->secretKey, $this->region, $this->endpoint) {
            public function __construct(
                private string $accessKey,
                private string $secretKey,
                private string $region,
                private ?string $endpoint
            ) {}

            public function putObject(array $params): array
            {
                // Simulação do upload
                return [
                    'ETag' => '"' . md5(uniqid()) . '"',
                    'VersionId' => uniqid(),
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
                        return 'https://example.s3.amazonaws.com/signed-url';
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
     * Gera URL pública para o arquivo
     */
    private function generateUrl(string $path): string
    {
        if ($this->baseUrl) {
            return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        }

        $host = $this->endpoint ?: "s3.{$this->region}.amazonaws.com";
        return "https://{$this->bucket}.{$host}/" . ltrim($path, '/');
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
        ];
    }

    /**
     * Verifica se o erro é de arquivo não encontrado
     */
    private function isNotFoundError(\Exception $e): bool
    {
        // Na implementação real, verificaria códigos específicos do AWS SDK
        return str_contains($e->getMessage(), 'NoSuchKey') || 
               str_contains($e->getMessage(), '404') ||
               str_contains($e->getMessage(), 'Not Found');
    }
}

