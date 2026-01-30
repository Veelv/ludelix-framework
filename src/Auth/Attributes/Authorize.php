<?php

namespace Ludelix\Auth\Attributes;

use Attribute;

/**
 * Authorize - Protects a method or controller.
 * 
 * When applied to a method, it ensures the user is authenticated via JWT.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Authorize
{
    /**
     * @param string|array $roles Optional roles required to access the endpoint.
     */
    public function __construct(
        public string|array $roles = []
    ) {
    }
}
