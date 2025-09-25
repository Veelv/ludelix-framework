<?php

namespace Ludelix\Ludou\Core;

use Ludelix\Interface\Template\TemplateParserInterface;

/**
 * Template Parser
 * 
 * Analyzes and validates template syntax
 */
class TemplateParser implements TemplateParserInterface
{
    public function parse(string $template): array
    {
        return [
            'expressions' => $this->extractExpressions($template),
            'directives' => $this->extractDirectives($template),
            'sections' => $this->extractSections($template),
            'extends' => $this->extractExtends($template),
        ];
    }

    public function extractExpressions(string $template): array
    {
        preg_match_all('/#\[([^\]]+)\]/', $template, $matches, PREG_SET_ORDER);
        return array_map(fn($m) => ['full' => $m[0], 'content' => trim($m[1])], $matches);
    }

    public function extractDirectives(string $template): array
    {
        $patterns = ['if', 'elseif', 'else', 'endif', 'foreach', 'endforeach'];
        $directives = [];
        
        foreach ($patterns as $pattern) {
            preg_match_all("/#$pattern/", $template, $matches);
            foreach ($matches[0] as $match) {
                $directives[] = ['type' => $pattern, 'full' => $match];
            }
        }
        
        // Extract foreach expressions for validation
        preg_match_all('/#foreach\s*\(([^)]+)\)/', $template, $foreachMatches, PREG_SET_ORDER);
        foreach ($foreachMatches as $match) {
            $directives[] = [
                'type' => 'foreach_expression', 
                'full' => $match[0], 
                'expression' => trim($match[1])
            ];
        }
        
        return $directives;
    }

    public function validate(string $template): bool
    {
        // Basic validation - check balanced directives
        $opens = ['#if', '#foreach'];
        $closes = ['#endif', '#endforeach'];
        
        foreach ($opens as $i => $open) {
            $openCount = substr_count($template, $open);
            $closeCount = substr_count($template, $closes[$i]);
            
            if ($openCount !== $closeCount) {
                throw new \Exception("Unbalanced directive: $open");
            }
        }
        
        // Validate foreach expressions
        preg_match_all('/#foreach\s*\(([^)]+)\)/', $template, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $expression = trim($match[1]);
            $this->validateForeachExpression($expression);
        }
        
        return true;
    }
    
    protected function validateForeachExpression(string $expression): void
    {
        // Handle "array as item" syntax: #foreach($users as $user)
        if (strpos($expression, ' as ') !== false) {
            $parts = explode(' as ', $expression, 2);
            $arrayVar = trim($parts[0]);
            $itemVar = trim($parts[1]);
            
            // Check for key-value syntax: $array as $key => $value
            if (strpos($itemVar, ' => ') !== false) {
                $keyValueParts = explode(' => ', $itemVar, 2);
                $keyVar = trim($keyValueParts[0]);
                $valueVar = trim($keyValueParts[1]);
                
                if (!preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*$/', $arrayVar) ||
                    !preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $keyVar) ||
                    !preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $valueVar)) {
                    throw new \Exception("Invalid foreach key-value syntax: $expression. Use: #foreach(\$array as \$key => \$value)");
                }
            } else {
                // Simple array as item syntax
                if (!preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*$/', $arrayVar) ||
                    !preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $itemVar)) {
                    throw new \Exception("Invalid foreach syntax: $expression. Use: #foreach(\$array as \$item)");
                }
            }
        }
        // Handle simple array: #foreach($items)
        else if (preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]+\])*$/', $expression)) {
            // Valid simple foreach
        }
        else {
            throw new \Exception("Invalid foreach expression: $expression. Supported formats: #foreach(\$array), #foreach(\$array as \$item), #foreach(\$array as \$key => \$value)");
        }
    }

    protected function extractSections(string $template): array
    {
        preg_match_all('/#section\s+(\w+)/', $template, $matches, PREG_SET_ORDER);
        return array_map(fn($m) => ['name' => $m[1], 'full' => $m[0]], $matches);
    }

    protected function extractExtends(string $template): ?array
    {
        if (preg_match("/#extends\['([^']+)'\]/", $template, $matches)) {
            return ['layout' => $matches[1], 'full' => $matches[0]];
        }
        return null;
    }
}
