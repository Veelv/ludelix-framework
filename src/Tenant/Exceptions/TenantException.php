<?php

namespace Ludelix\Tenant\Exceptions;

/**
 * Base Tenant Exception
 */
class TenantException extends \Exception
{
    protected array $context = [];

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}