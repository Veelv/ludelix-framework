<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Scopes an entity to a specific tenant.
 *
 * This attribute is used to automatically apply multi-tenancy constraints
 * to queries involving this entity, filtering by the tenant ID.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class TenantScoped
{
    /**
     * @param string $tenantColumn The database column name storing the tenant ID.
     * @param bool   $autoScope    Whether to automatically apply the tenant WHERE clause.
     */
    public function __construct(
        public string $tenantColumn = 'tenant_id',
        public bool $autoScope = true
    ) {
    }
}