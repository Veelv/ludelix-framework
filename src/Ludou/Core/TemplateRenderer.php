<?php

namespace Ludelix\Ludou\Core;

use Ludelix\Interface\Template\TemplateRendererInterface;

/**
 * Template Renderer
 * 
 * Executes compiled templates with data context
 */
class TemplateRenderer implements TemplateRendererInterface
{
    protected array $globals = [];
    public array $functions = [];
    public array $filters = [];

    public function render(string $compiledTemplate, array $data = []): string
    {
        $templateData = array_merge($this->globals, $data);
        extract($templateData, EXTR_SKIP);
        
        // Make renderer available for compiled template
        $renderer = $this;
        $__sections = [];
        
        ob_start();
        try {
            eval('?>' . $compiledTemplate);
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    public function setGlobals(array $globals): void
    {
        $this->globals = $globals;
    }

    public function addGlobal(string $key, mixed $value): void
    {
        $this->globals[$key] = $value;
    }

    public function setFunctions(array $functions): void
    {
        $this->functions = $functions;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }
}
