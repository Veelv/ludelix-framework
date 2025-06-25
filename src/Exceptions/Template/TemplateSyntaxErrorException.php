<?php

namespace Ludelix\Exceptions\Template;

use Exception;

/**
 * Template Syntax Error Exception
 * 
 * Thrown when template syntax validation fails.
 * Provides detailed error information for debugging template issues.
 */
class TemplateSyntaxErrorException extends Exception
{
    /**
     * Template file path where error occurred
     *
     * @var string|null
     */
    protected ?string $templatePath = null;

    /**
     * Line number where error occurred
     *
     * @var int|null
     */
    protected ?int $templateLine = null;

    /**
     * Set template context information
     *
     * @param string $templatePath Template file path
     * @param int|null $line Line number
     * @return self
     */
    public function setTemplateContext(string $templatePath, ?int $line = null): self
    {
        $this->templatePath = $templatePath;
        $this->templateLine = $line;
        
        return $this;
    }

    /**
     * Get template file path
     *
     * @return string|null
     */
    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    /**
     * Get template line number
     *
     * @return int|null
     */
    public function getTemplateLine(): ?int
    {
        return $this->templateLine;
    }

    /**
     * Get formatted error message with context
     *
     * @return string
     */
    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();
        
        if ($this->templatePath) {
            $message .= " in template: {$this->templatePath}";
        }
        
        if ($this->templateLine) {
            $message .= " on line: {$this->templateLine}";
        }
        
        return $message;
    }
}