<?php

namespace Ludelix\Database\Core;

class ConfigurationValidator
{
    /**
     * Valida se a configuração de banco de dados está correta
     */
    public static function validate(array $config): void
    {
        $default = $config['default'] ?? null;
        
        if (!$default) {
            throw new \InvalidArgumentException(
                "Nenhuma conexão padrão definida. Configure 'default' em config/database.php"
            );
        }
        
        if (!isset($config['connections'][$default])) {
            throw new \InvalidArgumentException(
                "Conexão padrão '{$default}' não encontrada em config/database.php"
            );
        }
        
        $connection = $config['connections'][$default];
        
        // Validar driver
        if (!isset($connection['driver'])) {
            throw new \InvalidArgumentException(
                "Driver não especificado para conexão '{$default}'"
            );
        }
        
        // Validar configurações específicas por driver
        match($connection['driver']) {
            'mysql' => self::validateMySQLConfig($connection, $default),
            'pgsql' => self::validatePgSQLConfig($connection, $default),
            'sqlite' => self::validateSQLiteConfig($connection, $default),
            default => throw new \InvalidArgumentException(
                "Driver '{$connection['driver']}' não suportado"
            )
        };
    }
    
    /**
     * Valida configuração MySQL
     */
    private static function validateMySQLConfig(array $connection, string $name): void
    {
        $required = ['host', 'port', 'database', 'username'];
        
        foreach ($required as $field) {
            if (!isset($connection[$field]) || empty($connection[$field])) {
                throw new \InvalidArgumentException(
                    "Configuração MySQL incompleta para '{$name}'. Campo '{$field}' é obrigatório."
                );
            }
        }
    }
    
    /**
     * Valida configuração PostgreSQL
     */
    private static function validatePgSQLConfig(array $connection, string $name): void
    {
        $required = ['host', 'port', 'database', 'username'];
        
        foreach ($required as $field) {
            if (!isset($connection[$field]) || empty($connection[$field])) {
                throw new \InvalidArgumentException(
                    "Configuração PostgreSQL incompleta para '{$name}'. Campo '{$field}' é obrigatório."
                );
            }
        }
    }
    
    /**
     * Valida configuração SQLite
     */
    private static function validateSQLiteConfig(array $connection, string $name): void
    {
        if (!isset($connection['database']) || empty($connection['database'])) {
            throw new \InvalidArgumentException(
                "Configuração SQLite incompleta para '{$name}'. Campo 'database' é obrigatório."
            );
        }
        
        // Verificar se o diretório do arquivo SQLite existe
        $databasePath = $connection['database'];
        $directory = dirname($databasePath);
        
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \InvalidArgumentException(
                    "Não foi possível criar o diretório para o arquivo SQLite: {$directory}"
                );
            }
        }
    }
    
    /**
     * Verifica se a configuração está usando valores padrão (não configurada explicitamente)
     */
    public static function isUsingDefaultValues(array $config): bool
    {
        $default = $config['default'] ?? 'mysql';
        $connection = $config['connections'][$default] ?? [];
        
        // Se for MySQL, verificar se está usando valores padrão
        if (($connection['driver'] ?? '') === 'mysql') {
            $defaultValues = [
                'host' => 'localhost',
                'port' => '3306',
                'database' => 'ludelix_app',
                'username' => 'root',
                'password' => ''
            ];
            
            foreach ($defaultValues as $field => $defaultValue) {
                if (($connection[$field] ?? '') === $defaultValue) {
                    return true;
                }
            }
        }
        
        return false;
    }
} 