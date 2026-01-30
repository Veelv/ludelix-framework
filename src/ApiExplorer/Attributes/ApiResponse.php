<?php

namespace Ludelix\ApiExplorer\Attributes;

use Attribute;

/**
 * Attribute to define a potential API response.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiResponse
{
    /**
     * @param int         $status      The HTTP status code.
     * @param string      $description A description of when this response is returned.
     * @param string|null $type        The expected response structure/type.
     */
    public function __construct(
        public int $status,
        public string $description = '',
        public ?string $type = null
    ) {
    }
}
