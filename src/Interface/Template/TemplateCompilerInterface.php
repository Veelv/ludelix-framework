<?php

namespace Ludelix\Interface\Template;

/**
 * Template Compiler Interface
 * 
 * Defines the contract for compiling template files into executable PHP code.
 * Handles Sharp syntax (#[]) compilation and directive processing.
 */
interface TemplateCompilerInterface
{
    /**
     * Compile template content into executable PHP code
     *
     * @param string $template Raw template content
     * @param array $functions Available template functions
     * @param array $filters Available template filters
     * @return string Compiled PHP code
     */
    public function compile(string $template, array $functions = [], array $filters = []): string;

    /**
     * Register a custom directive
     *
     * @param string $name Directive name
     * @param callable $handler Directive handler
     * @return void
     */
    public function directive(string $name, callable $handler): void;

    /**
     * Check if template needs recompilation
     *
     * @param string $templatePath Template file path
     * @param string $compiledPath Compiled file path
     * @return bool True if recompilation needed
     */
    public function needsRecompilation(string $templatePath, string $compiledPath): bool;
}