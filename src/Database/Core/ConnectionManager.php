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