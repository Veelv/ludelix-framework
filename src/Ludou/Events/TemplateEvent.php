<?php

namespace Ludelix\Ludou\Events;

/**
 * Base Template Event
 * 
 * Base class for all template-related events
 */
abstract class TemplateEvent
{
    protected string $templateName;
    protected array $data;
    protected float $timestamp;

    public function __construct(string $templateName, array $data = [])
    {
        $this->templateName = $templateName;
        $this->data = $data;
        $this->timestamp = microtime(true);
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function addData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function hasData(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function removeData(string $key): void
    {
        unset($this->data[$key]);
    }
}
