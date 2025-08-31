<?php

namespace Ludelix\Core\Console\Commands\Evolution;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Core\ConnectionManager;
use Ludelix\Database\Core\UnitOfWork;
use Ludelix\Database\Metadata\MetadataFactory;

class EvolveStatusCommand extends BaseCommand
{
    protected string $signature = 'evolve:status';
    protected string $description = 'Show status of evolutions';

    protected EntityManager $entityManager;
    protected ConnectionManager $connectionManager;

    public function execute(array $arguments, array $options): int
    {
        $this->info("📊 Evolution Status");
        $this->line("");

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
            $allEvolutions = $this->getAllEvolutions($evolutionPath);
            
            if (empty($allEvolutions)) {
                $this->info("📝 No evolution files found in {$evolutionPath}");
                return 0;
            }

            $this->displayStatus($allEvolutions, $appliedEvolutions);

            return 0;

        } catch (\Throwable $e) {
            $this->error("❌ Status check failed: " . $e->getMessage());
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
            $this->info("🔌 Checking database connection...");
            
            // Tentar conectar com MySQL primeiro
            $connection = $this->connectionManager->getConnection();
            
            if ($connection) {
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
            
            $sql = "SELECT evolution_id, applied_at, description FROM evolutions ORDER BY applied_at ASC";
            $stmt = $connection->query($sql);
            
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $applied = [];
            foreach ($result as $row) {
                $applied[$row['evolution_id']] = [
                    'applied_at' => $row['applied_at'],
                    'description' => $row['description']
                ];
            }
            
            return $applied;
            
        } catch (\Exception $e) {
            $this->error("Failed to get applied evolutions: " . $e->getMessage());
            return [];
        }
    }

    protected function getAllEvolutions(string $path): array
    {
        $evolutions = [];
        
        if (!is_dir($path)) {
            return $evolutions;
        }

        $files = glob($path . '*.php');
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $evolutions[] = [
                'id' => $filename,
                'description' => $this->getEvolutionDescription($file),
                'file' => $file
            ];
        }

        // Ordenar por ID (timestamp)
        usort($evolutions, function($a, $b) {
            return strcmp($a['id'], $b['id']);
        });

        return $evolutions;
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

    protected function displayStatus(array $allEvolutions, array $appliedEvolutions): void
    {
        $this->info("📋 Evolution Status Summary:");
        $this->line("");

        $pending = 0;
        $applied = 0;

        foreach ($allEvolutions as $evolution) {
            $status = isset($appliedEvolutions[$evolution['id']]) ? '✅ Applied' : '⏳ Pending';
            $appliedAt = isset($appliedEvolutions[$evolution['id']]) 
                ? $appliedEvolutions[$evolution['id']]['applied_at'] 
                : '';

            if ($status === '✅ Applied') {
                $applied++;
            } else {
                $pending++;
            }

            $this->line("  {$status} - {$evolution['id']}");
            $this->line("    Description: {$evolution['description']}");
            
            if ($appliedAt) {
                $this->line("    Applied at: {$appliedAt}");
            }
            
            $this->line("");
        }

        $this->info("📊 Summary:");
        $this->line("  • Total evolutions: " . count($allEvolutions));
        $this->line("  • Applied: {$applied}");
        $this->line("  • Pending: {$pending}");

        if ($pending > 0) {
            $this->line("");
            $this->info("💡 To apply pending evolutions, run:");
            $this->line("   php mi evolve:apply");
        }
    }
}