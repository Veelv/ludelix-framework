<?php

namespace Ludelix\Core\Console\Commands\Core;

use Ludelix\Interface\DI\ContainerInterface;
use Ludelix\Core\Console\Engine\MiEngine;
use Ludelix\Core\Console\Engine\OutputFormatter;

abstract class BaseCommand
{
    protected $container;
    protected MiEngine $engine;
    protected OutputFormatter $output;
    protected string $signature = '';
    protected string $description = '';

    public function __construct($container, MiEngine $engine)
    {
        $this->container = $container;
        $this->engine = $engine;
        $this->output = new OutputFormatter();
    }

    abstract public function execute(array $arguments, array $options): int;

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    protected function argument(array $arguments, int $index, mixed $default = null): mixed
    {
        return $arguments[$index] ?? $default;
    }

    protected function option(array $options, string $key, mixed $default = null): mixed
    {
        return $options[$key] ?? $default;
    }

    protected function hasOption(array $options, string $key): bool
    {
        return isset($options[$key]);
    }

    protected function fireHook(string $event, mixed $data = null): array
    {
        return $this->engine->getHooks()->fire($event, $data);
    }

    protected function info(string $message): void
    {
        $this->output->info($message);
        $logger = null;
        try {
            $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
        } catch (\Throwable $e) {
        }
        if ($logger)
            $logger->info($message);
    }

    protected function success(string $message): void
    {
        $this->output->success($message);
        $logger = null;
        try {
            $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
        } catch (\Throwable $e) {
        }
        if ($logger)
            $logger->info($message);
    }

    protected function error(string $message): void
    {
        $this->output->error($message);
        $logger = null;
        try {
            $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
        } catch (\Throwable $e) {
        }
        if ($logger)
            $logger->error($message);
    }

    protected function warning(string $message): void
    {
        $this->output->warning($message);
        $logger = null;
        try {
            $logger = \Ludelix\Bridge\Bridge::instance()->get('logger');
        } catch (\Throwable $e) {
        }
        if ($logger)
            $logger->warning($message);
    }

    protected function line(string $message = ''): void
    {
        $this->output->line($message);
    }

    /**
     * Display a table
     */
    protected function table(array $headers, array $rows): void
    {
        $this->output->table($headers, $rows);
    }

    /**
     * Display a progress bar
     */
    protected function progressBar(int $current, int $total, int $width = 50): void
    {
        $this->output->progressBar($current, $total, $width);
    }

    /**
     * Ask a question
     */
    protected function ask(string $question, ?string $default = null): string
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Confirm an action
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        return $this->output->confirm($question, $default);
    }

    /**
     * Give a choice
     */
    protected function choice(string $question, array $choices, ?string $default = null): string
    {
        return $this->output->choice($question, $choices, $default);
    }

    /**
     * Get a service from the container
     * 
     * @param string $name Service name
     * @return mixed
     */
    protected function service(string $name)
    {
        if ($this->container && method_exists($this->container, 'get')) {
            return $this->container->get($name);
        }

        // Fallback to Bridge if container doesn't have get method
        try {
            return \Ludelix\Bridge\Bridge::instance()->get($name);
        } catch (\Throwable $e) {
            throw new \Exception("Service '{$name}' not found: " . $e->getMessage());
        }
    }

    /**
     * Get extensions from the Mi instance
     * 
     * @return array
     */
    protected function getExtensions(): array
    {
        // We'll need to implement this properly
        // For now, return an empty array as a placeholder
        return [];
    }
}