<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Providers;

use Ludelix\PRT\ServiceProvider;
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
        $this->registerCommands();
    }

    /**
     * Executa após todos os providers serem registrados
     */
    public function boot(): void
    {
        $this->publishConfigurations();
        $this->registerMiddleware();
        $this->registerEventListeners();
    }

    /**
     * Registra as implementações dos contratos
     */
    private function registerContracts(): void
    {
        // Registrar FileValidator
        $this->app->singleton(FileValidatorInterface::class, function ($app) {
            return new FileValidator();
        });

        $this->app->singleton(FileValidator::class, function ($app) {
            return $app[FileValidatorInterface::class];
        });

        // Registrar MetadataExtractor
        $this->app->singleton(MetadataExtractorInterface::class, function ($app) {
            return new MetadataExtractor();
        });

        $this->app->singleton(MetadataExtractor::class, function ($app) {
            return $app[MetadataExtractorInterface::class];
        });

        // Registrar UploadProcessor
        $this->app->singleton(UploadProcessorInterface::class, function ($app) {
            return new UploadProcessor(
                $app[FileValidatorInterface::class],
                $app[MetadataExtractorInterface::class],
                $app[StorageInterface::class]
            );
        });
    }

    /**
     * Registra o StorageManager principal
     */
    private function registerStorageManager(): void
    {
        $this->app->singleton(StorageManager::class, function ($app) {
            $config = $app['config']['storage'] ?? [];
            
            $manager = new StorageManager(
                $app[FileValidatorInterface::class],
                $app[MetadataExtractorInterface::class],
                $config
            );

            // Registrar adaptadores configurados
            $disks = $config['disks'] ?? [];
            foreach ($disks as $name => $diskConfig) {
                try {
                    $adapter = $this->createAdapter($diskConfig);
                    $manager->addAdapter($name, $adapter);
                } catch (\Exception $e) {
                    // Log erro mas continue com outros adaptadores
                    if ($app->bound('log')) {
                        $app['log']->warning("Falha ao registrar adapter '{$name}': " . $e->getMessage());
                    }
                }
            }

            return $manager;
        });

        // Alias para facilitar injeção
        $this->app->alias(StorageManager::class, 'storage.manager');
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
     * Registra comandos Artisan
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Ludelix\Infrastructure\Commands\StorageStatsCommand::class,
                \Ludelix\Infrastructure\Commands\CleanupUploadsCommand::class,
                \Ludelix\Infrastructure\Commands\MigrateFilesCommand::class,
            ]);
        }
    }

    /**
     * Publica arquivos de configuração
     */
    private function publishConfigurations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Config/storage.php' => config_path('storage.php'),
            ], 'storage-config');

            $this->publishes([
                __DIR__ . '/../Config/upload-validation.php' => config_path('upload-validation.php'),
            ], 'storage-validation');
        }
    }

    /**
     * Registra middleware
     */
    private function registerMiddleware(): void
    {
        if ($this->app->bound('router')) {
            $router = $this->app['router'];
            $router->aliasMiddleware('upload.validate', \Ludelix\Infrastructure\Middleware\UploadValidationMiddleware::class);
            $router->aliasMiddleware('upload.limit', \Ludelix\Infrastructure\Middleware\UploadRateLimitMiddleware::class);
        }
    }

    /**
     * Registra event listeners
     */
    private function registerEventListeners(): void
    {
        if ($this->app->bound('events')) {
            $events = $this->app['events'];
            
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

