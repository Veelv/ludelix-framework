<?php

namespace Ludelix\Interface\Template;

interface TemplateEngineInterface
{
    public function render(string $template, array $data = []): string;
    public function compile(string $template): string;
    public function exists(string $template): bool;
    public function addPath(string $path): void;
    public function addGlobal(string $key, mixed $value): void;
    public function addFunction(string $name, callable $callback): void;
    public function addFilter(string $name, callable $callback): void;
}