<?php

namespace Ludelix\Ludou\Directives;

/**
 * Custom Directive
 * 
 * Allows registration of custom template directives
 */
class CustomDirective
{
    protected array $directives = [];

    public function register(string $name, callable $handler): void
    {
        $this->directives[$name] = $handler;
    }

    public function compile(string $template): string
    {
        foreach ($this->directives as $name => $handler) {
            $pattern = "/#$name(?:\\s*\\(([^)]*)\\))?/";
            
            $template = preg_replace_callback(
                $pattern,
                function ($matches) use ($handler) {
                    $params = $matches[1] ?? '';
                    return $handler($params);
                },
                $template
            );
        }

        return $template;
    }

    public function has(string $name): bool
    {
        return isset($this->directives[$name]);
    }

    public function getAll(): array
    {
        return array_keys($this->directives);
    }

    public function remove(string $name): void
    {
        unset($this->directives[$name]);
    }
}
