<?php

namespace Ludelix\Bootstrap\Runtime;

class EnvironmentLoader
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function load(): void
    {
        $envFile = $this->basePath . '/.env';
        
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Remove quotes
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                // Convert boolean strings but keep as string for putenv
                $envValue = $value;
                if (strtolower($value) === 'true') {
                    $_ENV[$name] = true;
                    putenv("$name=true");
                } elseif (strtolower($value) === 'false') {
                    $_ENV[$name] = false;
                    putenv("$name=false");
                } elseif (strtolower($value) === 'null') {
                    $_ENV[$name] = null;
                    putenv("$name=");
                } else {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }
    }
}