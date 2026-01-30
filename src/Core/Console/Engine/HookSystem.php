<?php

namespace Ludelix\Core\Console\Engine;

class HookSystem
{
    protected array $listeners = [];
    protected array $priorities = [];

    public function listen(string $event, callable $callback, int $priority = 0): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
            $this->priorities[$event] = [];
        }

        $this->listeners[$event][] = $callback;
        $this->priorities[$event][] = $priority;

        // Sort by priority (higher priority first)
        array_multisort(
            $this->priorities[$event], 
            SORT_DESC, 
            $this->listeners[$event]
        );
    }

    public function fire(string $event, mixed $data = null): array
    {
        $results = [];

        if (!isset($this->listeners[$event])) {
            return $results;
        }

        foreach ($this->listeners[$event] as $callback) {
            try {
                $result = call_user_func($callback, $data);
                $results[] = $result;
                
                // If callback returns false, stop propagation
                if ($result === false) {
                    break;
                }
            } catch (\Throwable $e) {
                // Log error but continue with other listeners
                error_log("Hook error in event '{$event}': " . $e->getMessage());
            }
        }

        return $results;
    }

    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }

    public function getListeners(string $event = null): array
    {
        if ($event) {
            return $this->listeners[$event] ?? [];
        }

        return $this->listeners;
    }

    public function removeListeners(string $event): void
    {
        unset($this->listeners[$event], $this->priorities[$event]);
    }

    public function removeAllListeners(): void
    {
        $this->listeners = [];
        $this->priorities = [];
    }

    public function getAvailableEvents(): array
    {
        return [
            'mi.before_command' => 'Fired before executing any command',
            'mi.after_command' => 'Fired after executing any command',
            'mi.template_render' => 'Fired when rendering templates',
            'mi.file_generate' => 'Fired when generating files',
            'kria.before_module' => 'Fired before creating a module',
            'kria.after_module' => 'Fired after creating a module',
            'kria.before_repository' => 'Fired before creating a repository',
            'kria.after_repository' => 'Fired after creating a repository',
            'kria.before_service' => 'Fired before creating a service',
            'kria.after_service' => 'Fired after creating a service',
            'kria.before_entity' => 'Fired before creating an entity',
            'kria.after_entity' => 'Fired after creating an entity',
            'extension.loaded' => 'Fired when an extension is loaded',
            'extension.registered' => 'Fired when an extension is registered'
        ];
    }
}