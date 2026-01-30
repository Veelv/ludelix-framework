<?php

namespace Ludelix\Tenant\Exceptions;

class TenantNotFoundException extends TenantException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
}