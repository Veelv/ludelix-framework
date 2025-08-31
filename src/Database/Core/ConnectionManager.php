<?php

namespace Ludelix\Database\Core;

use Ludelix\Database\Drivers\MySQLDriver;
use Ludelix\Database\Drivers\PgSQLDriver;
use Ludelix\Database\Drivers\SQLiteDriver;
use PDO;

class ConnectionManager
{
    protected array $connections = [];
    protected array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        
        // Validar configuração antes de permitir conexões
        try {
            ConfigurationValidator::validate($config);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                "Configuração de banco de dados inválida: " . $e->getMessage()
            );
        }
        
        // Verificar se está usando valores padrão e avisar
        if (ConfigurationValidator::isUsingDefaultValues($config)) {
            throw new \InvalidArgumentException(
                "Configuração de banco de dados não encontrada. " .
                "Configure as variáveis de banco de dados no arquivo .env ou " .
                "defina explicitamente as configurações em config/database.php. " .
                "Exemplo para .env:\n" .
                "DB_CONNECTION=mysql\n" .
                "DB_HOST=localhost\n" .
                "DB_PORT=3306\n" .
                "DB_DATABASE=seu_banco\n" .
                "DB_USERNAME=seu_usuario\n" .
                "DB_PASSWORD=sua_senha"
            );
        }
    }
    
    public function getConnection(string $name = null): PDO
    {
        $name = $name ?: $this->config['default'];
        
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }
        
        return $this->connections[$name];
    }
    
    protected function createConnection(string $name): PDO
    {
        $config = $this->config['connections'][$name];
        
        return match($config['driver']) {
            'mysql' => (new MySQLDriver())->connect($config),
            'pgsql' => (new PgSQLDriver())->connect($config),
            'sqlite' => (new SQLiteDriver())->connect($config),
            default => throw new \InvalidArgumentException("Unsupported driver: {$config['driver']}")
        };
    }
}