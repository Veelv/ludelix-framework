<?php

namespace Ludelix\Ludou\Events;

/**
 * Pre Render Event
 * 
 * Fired before template rendering begins
 */
class PreRenderEvent extends TemplateEvent
{
    protected string $compiledTemplate;
    protected bool $cancelled = false;

    public function __construct(string $templateName, string $compiledTemplate, array $data = [])
    {
        parent::__construct($templateName, $data);
        $this->compiledTemplate = $compiledTemplate;
    }

    public function getCompiledTemplate(): string
    {
        return $this->compiledTemplate;
    }

    public function setCompiledTemplate(string $compiledTemplate): void
    {
        $this->compiledTemplate = $compiledTemplate;
    }

    public function cancel(): void
    {
        $this->cancelled = true;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function resume(): void
    {
        $this->cancelled = false;
    }
}
