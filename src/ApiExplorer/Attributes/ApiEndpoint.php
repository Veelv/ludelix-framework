<?php

namespace Ludelix\ApiExplorer\Attributes;

use Attribute;

/**
 * Attribute to define general API endpoint metadata.
 *
 * Used to specify the summary, description, and tags for categorizing
 * the endpoint in the API documentation.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class ApiEndpoint
{
    /**
     * @param string $summary     A brief summary of the endpoint's purpose.
     * @param string $description Detailed description of the operation.
     * @param array  $tags        List of tags for grouping (e.g., 'Users', 'Auth').
     */
    public function __construct(
        public string $summary,
        public string $description = '',
        public array $tags = []
    ) {
    }
}
