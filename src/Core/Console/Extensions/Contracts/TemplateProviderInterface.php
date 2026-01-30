<?php

namespace Ludelix\Core\Console\Extensions\Contracts;

interface TemplateProviderInterface
{
    /**
     * Get template paths provided by this extension
     * 
     * @return array Array of template name => path mappings
     */
    public function templates(): array;

    /**
     * Get template namespace
     */
    public function namespace(): string;

    /**
     * Get template variables
     */
    public function variables(): array;

    /**
     * Get template helpers/functions
     */
    public function helpers(): array;
}