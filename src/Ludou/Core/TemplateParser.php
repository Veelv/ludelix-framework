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
        
        return true;
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
