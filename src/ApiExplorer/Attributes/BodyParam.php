<?php

namespace Ludelix\ApiExplorer\Attributes;

use Attribute;

/**
 * Attribute to define an API request body parameter.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class BodyParam
{
    /**
     * @param string       $name        The name of the field in the request body.
     * @param string       $type        The data type (string, int, etc.).
     * @param bool         $required    Whether the field is mandatory.
     * @param string       $description A brief explanation of the field.
     * @param string|array $rules       Validation rules (e.g., 'required|email').
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
