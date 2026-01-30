<?php

namespace Ludelix\Interface\Template;

/**
 * Template Renderer Interface
 * 
 * Defines the contract for rendering compiled templates with data.
 * Handles template execution, variable injection, and output generation.
 */
interface TemplateRendererInterface
{
    /**
     * Render compiled template with data
     *
     * @param string $compiledTemplate Compiled PHP template
     * @param array $data Template variables
     * @return string Rendered output
     */
    public function render(string $compiledTemplate, array $data = []): string;

    /**
     * Set global variables available to all templates
     *
     * @param array $globals Global variables
     * @return void
     */
    public function setGlobals(array $globals): void;

    /**
     * Add a global variable
     *
     * @param string $key Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public function addGlobal(string $key, mixed $value): void;

    /**
     * Set template functions
     *
     * @param array $functions Available functions
     * @return void
     */
    public function setFunctions(array $functions): void;

    /**
     * Set template filters
     *
     * @param array $filters Available filters
     * @return void
     */
    public function setFilters(array $filters): void;
}