<?php

namespace Ludelix\Database\Drivers;

use PDO;

class SQLiteDriver
{
    public function connect(array $config): PDO
    {
        return new PDO("sqlite:{$config['database']}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}