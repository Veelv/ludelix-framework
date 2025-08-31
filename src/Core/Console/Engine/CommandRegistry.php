<?php

namespace Ludelix\Core\Console\Engine;

class CommandRegistry
{
    protected array $commands = [];
    protected array $aliases = [];

    public function register(string $name, string $class): void
    {
        $this->commands[$name] = $class;
    }

    public function alias(string $alias, string $command): void
    {
        $this->aliases[$alias] = $command;
    }

    public function get(string $name): ?string
    {
        // Check aliases first
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        return $this->commands[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]) || isset($this->aliases[$name]);
    }

    public function all(): array
    {
        return $this->commands;
    }

    public function getByPrefix(string $prefix): array
    {
        $filtered = [];
        foreach ($this->commands as $name => $class) {
            if (str_starts_with($name, $prefix)) {
                $filtered[$name] = $class;
            }
        }
        return $filtered;
    }

    public function getFrameworkCommands(): array
    {
        $framework = ['serve', 'cache:clear', 'route:list', 'migrate'];
        $filtered = [];
        
        foreach ($framework as $cmd) {
            if (isset($this->commands[$cmd])) {
                $filtered[$cmd] = $this->commands[$cmd];
            }
        }
        
        return $filtered;
    }

    public function getPluginCommands(): array
    {
        $filtered = [];
        foreach ($this->commands as $name => $class) {
            if (!str_starts_with($name, 'kria:') && 
                !str_starts_with($name, 'extension:') && 
                !in_array($name, ['serve', 'cache:clear', 'route:list', 'migrate'])) {
                $filtered[$name] = $class;
            }
        }
        return $filtered;
    }

    public function remove(string $name): void
    {
        unset($this->commands[$name]);
        
        // Remove aliases pointing to this command
        foreach ($this->aliases as $alias => $target) {
            if ($target === $name) {
                unset($this->aliases[$alias]);
            }
        }
    }

    public function count(): int
    {
        return count($this->commands);
    }
}