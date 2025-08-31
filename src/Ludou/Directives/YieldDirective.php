<?php

namespace Ludelix\Ludou\Directives;

/**
 * Yield Directive
 * 
 * Handles section output with #yield('section')
 */
class YieldDirective
{
    public function compile(string $template): string
    {
        return preg_replace_callback(
            "/#yield\\('([^']+)'\\)/",
            function ($matches) {
                $sectionName = $matches[1];
                return "<?php echo \$__sections['{$sectionName}'] ?? ''; ?>";
            },
            $template
        );
    }

    public function extractYields(string $template): array
    {
        preg_match_all("/#yield\\('([^']+)'\\)/", $template, $matches);
        return $matches[1] ?? [];
    }
}
