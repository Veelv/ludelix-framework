<?php

namespace Ludelix\Ludou\Core;

/**
 * Filter Manager
 * 
 * Manages template filters and their application
 */
class FilterManager
{
    protected array $filters = [];

    public function __construct()
    {
        $this->registerDefaultFilters();
    }

    public function register(string $name, callable $filter): void
    {
        $this->filters[$name] = $filter;
    }

    public function apply(mixed $value, string $filter): mixed
    {
        if (!isset($this->filters[$filter])) {
            throw new \Exception("Filter '$filter' not found");
        }

        return $this->filters[$filter]($value);
    }

    public function applyChain(mixed $value, array $filters): mixed
    {
        foreach ($filters as $filter) {
            $value = $this->apply($value, trim($filter));
        }
        return $value;
    }

    public function getAll(): array
    {
        return $this->filters;
    }

    public function has(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    protected function registerDefaultFilters(): void
    {
        $this->filters = [
            'upper' => fn($value) => strtoupper($value),
            'lower' => fn($value) => strtolower($value),
            'json' => fn($value) => json_encode($value),
            'escape' => fn($value) => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            'raw' => fn($value) => $value,
            'trim' => fn($value) => trim($value),
            'length' => fn($value) => is_countable($value) ? count($value) : strlen($value),
            'default' => fn($value, $default = '') => $value ?: $default,
            'date' => fn($value, $format = 'Y-m-d') => date($format, is_string($value) ? strtotime($value) : $value),
            'truncate' => fn($value, $length = 100) => strlen($value) > $length ? substr($value, 0, $length) . '...' : $value,
        ];
    }
}
