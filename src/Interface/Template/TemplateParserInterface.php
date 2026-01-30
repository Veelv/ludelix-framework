<?php

namespace Ludelix\Interface\Template;

/**
 * Template Parser Interface
 * 
 * Defines the contract for parsing template syntax and extracting components.
 * Handles Sharp expressions, directives, and template structure analysis.
 */
interface TemplateParserInterface
{
    /**
     * Parse template content and extract components
     *
     * @param string $template Raw template content
     * @return array Parsed template components
     */
    public function parse(string $template): array;

    /**
     * Extract Sharp expressions from template
     *
     * @param string $template Template content
     * @return array Found expressions
     */
    public function extractExpressions(string $template): array;

    /**
     * Extract directives from template
     *
     * @param string $template Template content
     * @return array Found directives
     */
    public function extractDirectives(string $template): array;

    /**
     * Validate template syntax
     *
     * @param string $template Template content
     * @return bool True if syntax is valid
     * @throws TemplateSyntaxErrorException
     */
    public function validate(string $template): bool;
}