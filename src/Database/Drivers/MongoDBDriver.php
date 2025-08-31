<?php

namespace Ludelix\Database\Drivers;

use PDO;

class MongoDBDriver
{
    public function connect(array $config): PDO
    {
        throw new \RuntimeException('MongoDB driver not implemented yet');
    }
}