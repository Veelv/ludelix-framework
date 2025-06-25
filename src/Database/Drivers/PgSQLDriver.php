<?php

namespace Ludelix\Database\Drivers;

use PDO;

class PgSQLDriver
{
    public function connect(array $config): PDO
    {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        
        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}