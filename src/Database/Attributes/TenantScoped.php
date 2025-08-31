<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TenantScoped
{
    public function __construct(
        public string $tenantColumn = 'tenant_id',
        public bool $autoScope = true
    ) {}
}