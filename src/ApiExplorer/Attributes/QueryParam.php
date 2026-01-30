<?php

namespace Ludelix\ApiExplorer\Attributes;

use Attribute;

/**
 * Attribute to define an API query parameter.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class QueryParam
{
    /**
     * @param string       $name        The name of the parameter.
     * @param string       $type        The data type (string, int, bool, etc.).
     * @param bool         $required    Whether the parameter is mandatory.
     * @param string       $description A brief explanation of the parameter.
     * @param string|array $rules       Validation rules (e.g., 'required|min:3').
     */
    public function __construct(
        public string $name,
        public string $type = 'string',
        public bool $required = false,
        public string $description = '',
        public string|array $rules = []
    ) {
    }
}
