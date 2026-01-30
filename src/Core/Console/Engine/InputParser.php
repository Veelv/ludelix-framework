<?php

namespace Ludelix\Core\Console\Engine;

class InputParser
{
    public function parse(array $argv): array
    {
        array_shift($argv); // Remove script name
        
        $result = [
            'command' => '',
            'arguments' => [],
            'options' => []
        ];

        if (empty($argv)) {
            return $result;
        }

        // First argument is the command
        $result['command'] = array_shift($argv);

        // Parse remaining arguments and options
        foreach ($argv as $arg) {
            if ($this->isOption($arg)) {
                $this->parseOption($arg, $result['options']);
            } else {
                $result['arguments'][] = $arg;
            }
        }

        return $result;
    }

    protected function isOption(string $arg): bool
    {
        return str_starts_with($arg, '-');
    }

    protected function parseOption(string $arg, array &$options): void
    {
        if (str_starts_with($arg, '--')) {
            $this->parseLongOption($arg, $options);
        } else {
            $this->parseShortOption($arg, $options);
        }
    }

    protected function parseLongOption(string $arg, array &$options): void
    {
        $arg = substr($arg, 2); // Remove --
        
        if (str_contains($arg, '=')) {
            [$key, $value] = explode('=', $arg, 2);
            $options[$key] = $this->parseValue($value);
        } else {
            $options[$arg] = true;
        }
    }

    protected function parseShortOption(string $arg, array &$options): void
    {
        $arg = substr($arg, 1); // Remove -
        
        // Handle multiple short options like -abc
        for ($i = 0; $i < strlen($arg); $i++) {
            $options[$arg[$i]] = true;
        }
    }

    protected function parseValue(string $value): mixed
    {
        // Parse boolean values
        if (in_array(strtolower($value), ['true', '1', 'yes', 'on'])) {
            return true;
        }
        
        if (in_array(strtolower($value), ['false', '0', 'no', 'off'])) {
            return false;
        }

        // Parse numeric values
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // Parse arrays (comma-separated)
        if (str_contains($value, ',')) {
            return array_map('trim', explode(',', $value));
        }

        return $value;
    }

    public function hasOption(array $options, string $key): bool
    {
        return isset($options[$key]);
    }

    public function getOption(array $options, string $key, mixed $default = null): mixed
    {
        return $options[$key] ?? $default;
    }

    public function getArgument(array $arguments, int $index, mixed $default = null): mixed
    {
        return $arguments[$index] ?? $default;
    }
}