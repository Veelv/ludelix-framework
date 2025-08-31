<?php

namespace Ludelix\Bridge\Resolver;

use Ludelix\Bridge\Context\ExecutionContext;

/**
 * Contextual Resolver
 * 
 * Responsible for resolving services based on current execution context.
 */
class ContextualResolver
{
    protected ServiceResolver $serviceResolver;
    protected ExecutionContext $executionContext;
    protected array $config;

    public function __construct(
        ServiceResolver $serviceResolver,
        ExecutionContext $executionContext,
        array $config = []
    ) {
        $this->serviceResolver = $serviceResolver;
        $this->executionContext = $executionContext;
        $this->config = $config;
    }

    public function resolve(string $service, array $context = []): mixed
    {
        return $this->serviceResolver->resolve($service, $context);
    }

    public function setContext(array $context): void
    {
        // Context management logic
    }

    public function getContext(): array
    {
        return $this->executionContext->getCurrentContext();
    }
}
