<?php

namespace Ludelix\Ludou\Core;

use Ludelix\Interface\Template\TemplateCompilerInterface;

/**
 * SharpTemplate Compiler
 * 
 * Compiles .ludou templates with Sharp syntax (#[]) into executable PHP code
 */
class TemplateCompiler implements TemplateCompilerInterface
{
    protected array $directives = [];

    public function __construct()
    {
        $this->registerDirectives();
    }

    public function compile(string $template, array $functions = [], array $filters = []): string
    {
        // Simple approach - functions and filters will be available in renderer context
        $compiled = "<?php\n// Template compiled\n?>\n";

        // Process Sharp expressions #[...]
        $template = preg_replace_callback('/#\[([^\]]+)\]/', function($matches) {
            return $this->compileExpression(trim($matches[1]));
        }, $template);

        // Process extends
        $template = preg_replace("/#extends\['([^']+)'\]/", "<?php \$__layout = '$1'; ?>", $template);

        // Process sections
        $template = preg_replace('/#section\s+(\w+)/', '<?php ob_start(); ?>', $template);
        $template = preg_replace('/#endsection/', '<?php $__sections["$1"] = ob_get_clean(); ?>', $template);

        // Process directives
        foreach ($this->directives as $pattern => $replacement) {
            $template = preg_replace($pattern, $replacement, $template);
        }

        return $compiled . $template;
    }

    public function directive(string $name, callable $handler): void
    {
        $this->directives["/#$name/"] = $handler;
    }

    public function needsRecompilation(string $templatePath, string $compiledPath): bool
    {
        return !file_exists($compiledPath) || filemtime($templatePath) > filemtime($compiledPath);
    }

    protected function compileExpression(string $expression): string
    {
        // Function with filters: t('key')|upper|json
        if (preg_match("/^(\w+)\(([^)]*)\)(?:\|(.+))?$/", $expression, $matches)) {
            $func = $matches[1];
            $params = $matches[2];
            $filters = isset($matches[3]) ? explode('|', $matches[3]) : [];

            $code = "\$renderer->functions['$func']($params)";
            foreach ($filters as $filter) {
                $code = "\$renderer->filters['" . trim($filter) . "']($code)";
            }
            return "<?php echo $code; ?>";
        }

        // Variable with filters: $name|upper|json
        if (preg_match("/^([^|]+)(?:\|(.+))?$/", $expression, $matches)) {
            $var = trim($matches[1]);
            $filters = isset($matches[2]) ? explode('|', trim($matches[2])) : [];

            $code = $var;
            foreach ($filters as $filter) {
                $code = "\$renderer->filters['" . trim($filter) . "']($code)";
            }
            return "<?php echo $code; ?>";
        }

        return "<?php echo $expression; ?>";
    }

    protected function registerDirectives(): void
    {
        $this->directives = [
            '/#if\s*\(([^)]+)\)/' => '<?php if ($1): ?>',
            '/#elseif\s*\(([^)]+)\)/' => '<?php elseif ($1): ?>',
            '/#else/' => '<?php else: ?>',
            '/#endif/' => '<?php endif; ?>',
            '/#foreach\s*\(([^)]+)\)/' => '<?php foreach ($1): ?>',
            '/#endforeach/' => '<?php endforeach; ?>',
        ];
    }
}
