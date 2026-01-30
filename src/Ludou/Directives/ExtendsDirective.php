<?php

namespace Ludelix\Ludou\Directives;

/**
 * Extends Directive
 * 
 * Handles template inheritance with #extends['layout']
 */
class ExtendsDirective
{
    public function compile(string $expression): string
    {
        // Extract layout name from #extends['layout']
        if (preg_match("/#extends\\['([^']+)'\\]/", $expression, $matches)) {
            $layout = $matches[1];
            return "<?php \$__layout = '{$layout}'; ?>";
        }

        return $expression;
    }

    public function process(string $template): string
    {
        return preg_replace_callback(
            "/#extends\\['([^']+)'\\]/",
            fn($matches) => $this->compile($matches[0]),
            $template
        );
    }

    public function extractLayout(string $template): ?string
    {
        if (preg_match("/#extends\\['([^']+)'\\]/", $template, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
