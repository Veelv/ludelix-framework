<?php

namespace Ludelix\Core\Console\Templates\Engine;

class TemplateEngine
{
    protected array $templatePaths = [];
    protected array $variables = [];
    protected array $helpers = [];

    public function __construct()
    {
        $this->registerDefaultHelpers();
    }

    public function addPath(string $name, string $path): void
    {
        $this->templatePaths[$name] = $path;
    }

    public function render(string $template, array $variables = []): string
    {
        $templatePath = $this->findTemplate($template);
        
        if (!$templatePath) {
            throw new \Exception("Template '{$template}' not found");
        }

        $content = file_get_contents($templatePath);
        $variables = array_merge($this->variables, $variables);
        
        return $this->processTemplate($content, $variables);
    }

    public function setVariable(string $key, mixed $value): void
    {
        $this->variables[$key] = $value;
    }

    public function setVariables(array $variables): void
    {
        $this->variables = array_merge($this->variables, $variables);
    }

    public function addHelper(string $name, callable $helper): void
    {
        $this->helpers[$name] = $helper;
    }

    protected function findTemplate(string $template): ?string
    {
        // Try with .lux extension
        $templateFile = $template . '.lux';
        
        // Search in registered paths
        foreach ($this->templatePaths as $path) {
            $fullPath = $path . '/' . $templateFile;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        // Try direct path
        if (file_exists($template)) {
            return $template;
        }

        return null;
    }

    protected function processTemplate(string $content, array $variables): string
    {
        // Process simple variables {{variable}}
        $content = $this->processVariables($content, $variables);
        
        // Process conditionals {{#if condition}}
        $content = $this->processConditionals($content, $variables);
        
        // Process loops {{#each items}}
        $content = $this->processLoops($content, $variables);
        
        // Process helpers
        $content = $this->processHelpers($content, $variables);
        
        return $content;
    }

    protected function processVariables(string $content, array $variables): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($variables) {
            $key = $matches[1];
            return $variables[$key] ?? $matches[0];
        }, $content);
    }

    protected function processConditionals(string $content, array $variables): string
    {
        return preg_replace_callback('/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s', function($matches) use ($variables) {
            $condition = $matches[1];
            $block = $matches[2];
            
            if (!empty($variables[$condition])) {
                return $this->processTemplate($block, $variables);
            }
            
            return '';
        }, $content);
    }

    protected function processLoops(string $content, array $variables): string
    {
        return preg_replace_callback('/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s', function($matches) use ($variables) {
            $arrayKey = $matches[1];
            $block = $matches[2];
            $result = '';
            
            if (isset($variables[$arrayKey]) && is_array($variables[$arrayKey])) {
                foreach ($variables[$arrayKey] as $item) {
                    $itemVars = is_array($item) ? $item : ['item' => $item];
                    $result .= $this->processTemplate($block, array_merge($variables, $itemVars));
                }
            }
            
            return $result;
        }, $content);
    }

    protected function processHelpers(string $content, array $variables): string
    {
        foreach ($this->helpers as $name => $helper) {
            $pattern = '/\{\{' . preg_quote($name) . '\((.*?)\)\}\}/';
            $content = preg_replace_callback($pattern, function($matches) use ($helper, $variables) {
                $args = $this->parseHelperArgs($matches[1], $variables);
                return call_user_func_array($helper, $args);
            }, $content);
        }
        
        return $content;
    }

    protected function parseHelperArgs(string $argsString, array $variables): array
    {
        $args = [];
        $parts = explode(',', $argsString);
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            // Check if it's a variable
            if (isset($variables[$part])) {
                $args[] = $variables[$part];
            } else {
                // Parse as literal
                $args[] = trim($part, '"\'');
            }
        }
        
        return $args;
    }

    protected function registerDefaultHelpers(): void
    {
        $this->addHelper('studly', function($value) {
            return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
        });

        $this->addHelper('snake', function($value) {
            return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
        });

        $this->addHelper('kebab', function($value) {
            return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $value));
        });

        $this->addHelper('plural', function($value) {
            return $value . 's'; // Simple pluralization
        });

        $this->addHelper('timestamp', function() {
            return date('Y-m-d H:i:s');
        });

        $this->addHelper('date', function($format = 'Y-m-d') {
            return date($format);
        });
    }
}