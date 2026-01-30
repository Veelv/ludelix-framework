<?php

namespace Ludelix\Core\Console\Commands\Evolution;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Core\Repository;
use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Core\UnitOfWork;
use Ludelix\Database\Metadata\MetadataFactory;

class EvolveApplyCommand extends BaseCommand
{
    protected string $signature = 'evolve:apply [--target=] [--dry-run]';
    protected string $description = 'Apply pending evolutions to database';

    protected EntityManager $entityManager;
    protected ConnectionManager $connectionManager;

    public function execute(array $arguments, array $options): int
    {
        $target = $this->option($options, 'target');
        $dryRun = $this->hasOption($options, 'dry-run');

        if ($dryRun) {
            $this->info("🔍 Dry run mode - no changes will be applied");
        }

        $this->info("🔄 Applying evolutions...");
        
        try {
            // Inicializar ORM do Ludelix
            $this->initializeORM();
            
            // Verificar conexão com banco de dados
            if (!$this->checkDatabaseConnection()) {
                $this->error("❌ Cannot connect to database. Please check your database configuration.");
                return 1;
            }

            // Criar tabela de evolutions se não existir
            $this->createEvolutionsTable();

            $evolutionPath = 'database/evolutions/';
            $appliedEvolutions = $this->getAppliedEvolutions();
            $pendingEvolutions = $this->getPendingEvolutions($evolutionPath, $appliedEvolutions);
            
            if (empty($pendingEvolutions)) {
                $this->info("✅ No pending evolutions to apply");
                return 0;
            }

            $applied = [];
            
            foreach ($pendingEvolutions as $evolution) {
                $this->info("📝 Applying evolution: {$evolution['id']}");
                
                if (!$dryRun) {
                    echo "\n=== DEBUG: Iniciando execução da evolution ===\n";
                    echo "Arquivo: " . $evolution['file'] . "\n";
                    echo "ID: " . $evolution['id'] . "\n";
                    echo "Descrição: " . $evolution['description'] . "\n";
                    echo "==========================================\n";
                    
                    if ($this->executeEvolution($evolution['file'])) {
                        $this->markEvolutionAsApplied($evolution['id']);
                        $applied[] = $evolution['id'];
                        $this->success("  ✅ Evolution applied successfully");
                    } else {
                        $this->error("  ❌ Failed to apply evolution");
                        return 1;
                    }
                } else {
                    $this->line("  • Would apply: {$evolution['description']}");
                }
            }

            if (!$dryRun) {
                $this->success("✅ Applied " . count($applied) . " evolutions:");
                foreach ($applied as $evolutionId) {
                    $this->line("  • {$evolutionId}");
                }
            } else {
                $this->info("✅ Would apply " . count($pendingEvolutions) . " evolutions");
            }

            return 0;

        } catch (\Throwable $e) {
            $this->error("❌ Evolution failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function initializeORM(): void
    {
        try {
            $this->info("🔌 Initializing Ludelix ORM...");
            
            // Carregar configuração do banco de dados
            $configPath = 'config/database.php';
            if (!file_exists($configPath)) {
                throw new \Exception("Database configuration file not found: {$configPath}");
            }
            
            $config = require $configPath;
            
            // Criar instâncias das dependências do ORM
            $this->connectionManager = new ConnectionManager($config);
            $metadataFactory = new MetadataFactory();
            $unitOfWork = new UnitOfWork();
            
            // Criar EntityManager com as dependências
            $this->entityManager = new EntityManager(
                $this->connectionManager,
                $metadataFactory,
                $unitOfWork
            );
            
            $this->success("✅ ORM initialized successfully");
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to initialize ORM: " . $e->getMessage());
        }
    }

    protected function checkDatabaseConnection(): bool
    {
        try {
            echo "=== DEBUG START ===\n";
            $this->info("🔌 Checking database connection...");
            
            // Mostrar qual configuração está sendo usada
            $config = require 'config/database.php';
            echo "DEBUG: Default connection: " . $config['default'] . "\n";
            echo "DEBUG: Available connections: " . implode(', ', array_keys($config['connections'])) . "\n";
            $this->line("  • Default connection: " . $config['default']);
            $this->line("  • Available connections: " . implode(', ', array_keys($config['connections'])));
            
            // Tentar conectar com MySQL primeiro
            $connection = $this->connectionManager->getConnection();
            
            if ($connection) {
                // Mostrar informações da conexão
                $dsn = $connection->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
                echo "DEBUG: Connection DSN: " . $dsn . "\n";
                echo "DEBUG: Database name: " . $connection->query('SELECT DATABASE()')->fetchColumn() . "\n";
                $this->line("  • Connection DSN: " . $dsn);
                $this->line("  • Database name: " . $connection->query('SELECT DATABASE()')->fetchColumn());
                $this->success("✅ Database connection established successfully");
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
            
            // Tentar criar o banco se for MySQL
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                $this->info("🔄 Attempting to create database...");
                if ($this->createDatabase()) {
                    return $this->checkDatabaseConnection();
                }
            }
            
            // Tentar usar SQLite como fallback
            $this->info("🔄 Trying SQLite as fallback...");
            if ($this->useSQLiteFallback()) {
                return $this->checkDatabaseConnection();
            }
            
            return false;
        }
    }

    protected function createDatabase(): bool
    {
        try {
            $config = require 'config/database.php';
            $connection = $config['connections']['mysql'];
            
            // Conectar sem especificar o banco
            $dsn = "mysql:host={$connection['host']};port={$connection['port']};charset={$connection['charset']}";
            $pdo = new \PDO($dsn, $connection['username'], $connection['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Criar o banco
            $database = $connection['database'];
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $this->success("✅ Database '{$database}' created successfully");
            return true;
            
        } catch (\Exception $e) {
            $this->error("Failed to create database: " . $e->getMessage());
            return false;
        }
    }

    protected function useSQLiteFallback(): bool
    {
        try {
            $this->info("🔄 Switching to SQLite...");
            
            // Modificar temporariamente a configuração para usar SQLite
            $config = require 'config/database.php';
            $config['default'] = 'sqlite';
            
            // Recriar ConnectionManager com SQLite
            $this->connectionManager = new ConnectionManager($config);
            
            $this->success("✅ Switched to SQLite successfully");
            return true;
            
        } catch (\Exception $e) {
            $this->error("Failed to switch to SQLite: " . $e->getMessage());
            return false;
        }
    }

    protected function createEvolutionsTable(): void
    {
        try {
            // Usar PDO diretamente para DDL (CREATE TABLE)
            $connection = $this->connectionManager->getConnection();
            
            $sql = "
                CREATE TABLE IF NOT EXISTS evolutions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    evolution_id VARCHAR(255) UNIQUE NOT NULL,
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    description TEXT,
                    execution_time FLOAT DEFAULT 0
                )
            ";
            
            $connection->exec($sql);
            $this->line("  • Evolutions table ready");
            
        } catch (\Exception $e) {
            $this->error("Failed to create evolutions table: " . $e->getMessage());
            throw $e;
        }
    }

    protected function getAppliedEvolutions(): array
    {
        try {
            // Usar PDO diretamente para consulta simples
            $connection = $this->connectionManager->getConnection();
            
            $sql = "SELECT evolution_id FROM evolutions ORDER BY applied_at ASC";
            $stmt = $connection->query($sql);
            
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
        } catch (\Exception $e) {
            $this->error("Failed to get applied evolutions: " . $e->getMessage());
            return [];
        }
    }

    protected function getPendingEvolutions(string $path, array $applied): array
    {
        $pending = [];
        
        if (!is_dir($path)) {
            return $pending;
        }

        $files = glob($path . '*.php');
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            
            // Verificar se já foi aplicada
            if (!in_array($filename, $applied)) {
                $pending[] = [
                    'id' => $filename,
                    'description' => $this->getEvolutionDescription($file),
                    'file' => $file
                ];
            }
        }

        // Ordenar por ID (timestamp)
        usort($pending, function($a, $b) {
            return strcmp($a['id'], $b['id']);
        });

        return $pending;
    }

    protected function getEvolutionDescription(string $file): string
    {
        // Tentar extrair descrição do arquivo
        $content = file_get_contents($file);
        
        // Procurar por padrões como "Create table" ou nome da classe
        if (preg_match('/createTable\(\'([^\']+)\'/', $content, $matches)) {
            return "Create {$matches[1]} table";
        }
        
        if (preg_match('/class\s+(\w+)Evolution/', $content, $matches)) {
            return ucfirst(str_replace('_', ' ', strtolower($matches[1])));
        }
        
        return "Evolution";
    }

    protected function executeEvolution(string $file): bool
    {
        try {
            $this->line("    📂 Loading evolution file: " . basename($file));
            
            // Carregar e executar a evolution
            $evolution = $this->loadEvolution($file);
            
            if ($evolution) {
                $this->line("    🔧 Executing evolution...");
                
                // Executar o método forward() da evolution
                $evolution->forward();
                
                $this->line("    ✅ Evolution executed successfully");
                return true;
            } else {
                $this->error("    ❌ Failed to load evolution object from file");
                return false;
            }
        } catch (\Throwable $e) {
            // Forçar saída do erro
            echo "\n";
            echo "=== ERRO DETALHADO ===\n";
            echo "Mensagem: " . $e->getMessage() . "\n";
            echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            echo "=====================\n";
            echo "\n";
            
            $this->error("    ❌ Evolution execution failed:");
            $this->error("       Error: " . $e->getMessage());
            $this->error("       File: " . $e->getFile() . ":" . $e->getLine());
            $this->error("       Stack trace:");
            $this->error("       " . $e->getTraceAsString());
            
            return false;
        }
    }

    protected function loadEvolution(string $file): ?object
    {
        try {
            $this->line("    📖 Including evolution file...");
            
            // Incluir o arquivo da evolution
            $evolution = include $file;
            
            if (is_object($evolution)) {
                $this->line("    ✅ Evolution object loaded: " . get_class($evolution));
                
                // Injetar dependências se for uma SchemaEvolution
                if ($evolution instanceof \Ludelix\Database\Evolution\Core\SchemaEvolution) {
                    $this->line("    🔧 Injecting dependencies...");
                    
                    // Usar reflection para injetar o connectionManager
                    $reflection = new \ReflectionClass($evolution);
                    $property = $reflection->getProperty('connectionManager');
                    $property->setAccessible(true);
                    $property->setValue($evolution, $this->connectionManager);
                    
                    $this->line("    ✅ Dependencies injected successfully");
                }
                
                return $evolution;
            } else {
                $this->error("    ❌ File did not return an object. Got: " . gettype($evolution));
                return null;
            }
        } catch (\Throwable $e) {
            $this->error("    ❌ Failed to load evolution file:");
            $this->error("       Error: " . $e->getMessage());
            $this->error("       File: " . $e->getFile() . ":" . $e->getLine());
            return null;
        }
    }

    protected function markEvolutionAsApplied(string $evolutionId): void
    {
        try {
            // Usar PDO diretamente para inserção simples
            $connection = $this->connectionManager->getConnection();
            
            $sql = "INSERT INTO evolutions (evolution_id, description) VALUES (?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$evolutionId, "Evolution applied"]);
            
            $this->line("  • Marked as applied: {$evolutionId}");
            
        } catch (\Exception $e) {
            $this->error("Failed to mark evolution as applied: " . $e->getMessage());
            throw $e;
        }
    }
}