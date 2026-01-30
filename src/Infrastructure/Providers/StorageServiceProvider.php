<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Providers;

use Ludelix\Bootstrap\Providers\ServiceProvider;
use Ludelix\Infrastructure\Core\StorageManager;
use Ludelix\Infrastructure\Core\FileValidator;
use Ludelix\Infrastructure\Core\MetadataExtractor;
use Ludelix\Infrastructure\Core\UploadProcessor;
use Ludelix\Infrastructure\Adapters\LocalAdapter;
use Ludelix\Infrastructure\Adapters\S3Adapter;
use Ludelix\Infrastructure\Adapters\DigitalOceanAdapter;
use Ludelix\Infrastructure\Adapters\CloudinaryAdapter;
use Ludelix\Infrastructure\Contracts\StorageInterface;
use Ludelix\Infrastructure\Contracts\FileValidatorInterface;
use Ludelix\Infrastructure\Contracts\MetadataExtractorInterface;
use Ludelix\Infrastructure\Contracts\UploadProcessorInterface;
use Ludelix\Infrastructure\Exceptions\StorageException;

/**
 * Service Provider para a Infraestrutura de Storage
 * 
 * Registra todos os componentes da infraestrutura de upload
 * no container de injeção de dependência do framework.
 */
class StorageServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços no container
     */
    public function register(): void
    {
        $this->registerContracts();
        $this->registerStorageManager();
        // $this->registerCommands(); // TODO: Implement command registration for this framework
    }

    /**
     * Executa após todos os providers serem registrados
     */
    public function boot(): void
    {
        // $this->publishConfigurations(); // TODO: Implement config publishing
        $this->registerMiddleware();
        $this->registerEventListeners();
    }

    /**
     * Registra as implementações dos contratos
     */
    private function registerContracts(): void
    {
        // Registrar FileValidator
        $this->container->singleton(FileValidatorInterface::class, function ($container) {
            return new FileValidator();
        });

        $this->container->singleton(FileValidator::class, function ($container) {
            return $container->get(FileValidatorInterface::class);
        });

        // Registrar MetadataExtractor
        $this->container->singleton(MetadataExtractorInterface::class, function ($container) {
            return new MetadataExtractor();
        });

        $this->container->singleton(MetadataExtractor::class, function ($container) {
            return $container->get(MetadataExtractorInterface::class);
        });

        // Registrar UploadProcessor
        $this->container->singleton(UploadProcessorInterface::class, function ($container) {
            return new UploadProcessor(
                $container->get(FileValidatorInterface::class),
                $container->get(MetadataExtractorInterface::class),
                $container->get(StorageInterface::class)
            );
        });
    }

    /**
     * Registra o StorageManager principal
     */
    private function registerStorageManager(): void
    {
        $this->container->singleton(StorageManager::class, function ($container) {
            // Assumindo que 'config' retorna um array ou objeto acessível
            $configService = $container->has('config') ? $container->get('config') : [];
            // Se for objeto, precisa converter ou acessar propriedade. Tratando como array por compatibilidade com código original
            $storageConfig = is_array($configService) ? ($configService['storage'] ?? []) : ($configService->get('storage') ?? []);
            
            $manager = new StorageManager(
                $container->get(FileValidatorInterface::class),
                $container->get(MetadataExtractorInterface::class),
                $storageConfig
            );

            // Registrar adaptadores configurados
            $disks = $storageConfig['disks'] ?? [];
            foreach ($disks as $name => $diskConfig) {
                try {
                    $adapter = $this->createAdapter($diskConfig);
                    $manager->addAdapter($name, $adapter);
                } catch (\Exception $e) {
                    // Log erro mas continue com outros adaptadores
                    if ($container->has('logger')) {
                        $container->get('logger')->warning("Falha ao registrar adapter '{$name}': " . $e->getMessage());
                    }
                }
            }

            return $manager;
        });

        // Alias para facilitar injeção
        $this->container->alias(StorageManager::class, 'storage.manager');
    }

    /**
     * Cria um adaptador baseado na configuração
     */
    private function createAdapter(array $config): StorageInterface
    {
        $driver = $config['driver'] ?? throw new StorageException('Driver não especificado na configuração');

        return match ($driver) {
            'local' => new LocalAdapter($config),
            's3' => new S3Adapter($config),
            'digitalocean' => new DigitalOceanAdapter($config),
            'cloudinary' => new CloudinaryAdapter($config),
            default => throw new StorageException("Driver não suportado: {$driver}")
        };
    }

    /**
     * Registra middleware
     */
    private function registerMiddleware(): void
    {
        if ($this->container->has('router')) {
            $router = $this->container->get('router');
            // Verifica se o router suporta aliasMiddleware (método comum em frameworks, mas validamos existência)
            if (method_exists($router, 'aliasMiddleware')) {
                $router->aliasMiddleware('upload.validate', \Ludelix\Infrastructure\Middleware\UploadValidationMiddleware::class);
                $router->aliasMiddleware('upload.limit', \Ludelix\Infrastructure\Middleware\UploadRateLimitMiddleware::class);
            }
        }
    }

    /**
     * Registra event listeners
     */
    private function registerEventListeners(): void
    {
        if ($this->container->has('events')) {
            $events = $this->container->get('events');
            
            // Listener para limpeza automática de uploads expirados
            $events->listen(
                \Ludelix\Infrastructure\Events\UploadCompleted::class,
                \Ludelix\Infrastructure\Listeners\CleanupExpiredUploads::class
            );

            // Listener para logging de uploads
            $events->listen(
                \Ludelix\Infrastructure\Events\UploadStarted::class,
                \Ludelix\Infrastructure\Listeners\LogUploadActivity::class
            );

            $events->listen(
                \Ludelix\Infrastructure\Events\UploadFailed::class,
                \Ludelix\Infrastructure\Listeners\LogUploadActivity::class
            );
        }
    }
    /**
     * Serviços fornecidos por este provider
     */
    public function provides(): array
    {
        return [
            StorageManager::class,
            FileValidatorInterface::class,
            MetadataExtractorInterface::class,
            UploadProcessorInterface::class,
            'storage.manager',
        ];
    }
}

