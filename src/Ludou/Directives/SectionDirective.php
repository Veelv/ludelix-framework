<?php

namespace Ludelix\Ludou\Directives;

/**
 * Section Directive
 * 
 * Handles template sections with #section name and #endsection
 */
class SectionDirective
{
    public function compile(string $template): string
    {
        // Process section start
        $template = preg_replace_callback(
            '/#section\\s+(\\w+)/',
            function ($matches) {
                $sectionName = $matches[1];
                return "<?php ob_start(); \$__currentSection = '{$sectionName}'; ?>";
            },
            $template
        );

        // Process section end
        $template = preg_replace(
            '/#endsection/',
            '<?php $__sections[$__currentSection] = ob_get_clean(); ?>',
            $template
        );

        return $template;
    }

    public function extractSections(string $template): array
    {
        preg_match_all('/#section\\s+(\\w+)/', $template, $matches);
        return $matches[1] ?? [];
    }

    public function validateSections(string $template): bool
    {
        $sectionCount = substr_count($template, '#section');
        $endSectionCount = substr_count($template, '#endsection');
        
        if ($sectionCount !== $endSectionCount) {
            throw new \Exception("Unmatched sections: {$sectionCount} #section but {$endSectionCount} #endsection");
        }

        return true;
    }
}
