<?php
namespace Ludelix\Bridge\Resolver;

/**
 * Class ContextualResolver
 *
 * Responsible for resolving services or dependencies based on the current context (e.g., tenant, request, environment).
 * Useful for advanced dependency injection scenarios where context-aware resolution is required.
 */
class ContextualResolver
{
    /**
     * The current context (could be tenant, request, etc).
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Set the current context.
     *
     * @param array $context
     * @return void
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * Get the current context.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Resolve a service or value based on the current context.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function resolve(string $key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Reset the context to its initial state.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->context = [];
    }
}
