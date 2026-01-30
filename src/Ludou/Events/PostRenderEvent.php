<?php

namespace Ludelix\Ludou\Events;

/**
 * Post Render Event
 * 
 * Fired after template rendering completes
 */
class PostRenderEvent extends TemplateEvent
{
    protected string $output;
    protected float $renderTime;

    public function __construct(string $templateName, string $output, array $data = [], float $renderTime = 0.0)
    {
        parent::__construct($templateName, $data);
        $this->output = $output;
        $this->renderTime = $renderTime;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    public function getRenderTime(): float
    {
        return $this->renderTime;
    }

    public function setRenderTime(float $renderTime): void
    {
        $this->renderTime = $renderTime;
    }

    public function appendOutput(string $content): void
    {
        $this->output .= $content;
    }

    public function prependOutput(string $content): void
    {
        $this->output = $content . $this->output;
    }
}
