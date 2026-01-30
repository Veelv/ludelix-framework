<?php

namespace Ludelix\ApiExplorer\Validation;

use Ludelix\ApiExplorer\Attributes\BodyParam;
use Ludelix\ApiExplorer\Attributes\QueryParam;
use Ludelix\Validation\Core\Validator;
use Ludelix\PRT\Request;
use ReflectionMethod;

/**
 * AttributeValidator - Bridges API attributes and the validation system.
 * 
 * Automatically performs validation based on #[QueryParam] and #[BodyParam] attributes.
 */
class AttributeValidator
{
    protected Validator $validator;

    /**
     * @param Validator $validator Injected validator instance.
     */
    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validates a request based on method attributes.
     *
     * @param Request          $request The current request.
     * @param ReflectionMethod $method  The controller method being executed.
     * @return array|null Returns error array if validation fails, null otherwise.
     */
    public function validate(Request $request, ReflectionMethod $method): ?array
    {
        $rules = [];

        // Extract BodyParam rules
        foreach ($method->getAttributes(BodyParam::class) as $attr) {
            $instance = $attr->newInstance();
            if (!empty($instance->rules)) {
                $rules[$instance->name] = is_string($instance->rules) ? explode('|', $instance->rules) : $instance->rules;
            } elseif ($instance->required) {
                $rules[$instance->name][] = 'required';
            }
        }

        // Validate Body (BodyParam)
        if (!empty($rules)) {
            $result = $this->validator->validate($request->all(), $rules);
            if ($result->fails()) {
                return $result->errors();
            }
        }

        // Extract QueryParam rules
        $queryRules = [];
        foreach ($method->getAttributes(QueryParam::class) as $attr) {
            $instance = $attr->newInstance();
            if (!empty($instance->rules)) {
                $queryRules[$instance->name] = is_string($instance->rules) ? explode('|', $instance->rules) : $instance->rules;
            } elseif ($instance->required) {
                $queryRules[$instance->name][] = 'required';
            }
        }

        // Validate Query (QueryParam)
        if (!empty($queryRules)) {
            // Note: We might need a way to get raw query params from Request if it merges them.
            // For now assume $request->all() includes both or we use a specific query source.
            $result = $this->validator->validate($request->all(), $queryRules);
            if ($result->fails()) {
                return $result->errors();
            }
        }

        return null;
    }
}
