<?php

namespace Ludelix\Ludou\Directives;

/**
 * Connect Directive
 * 
 * Handles #[connect] directive for SPA applications
 * Similar to @inertia in Laravel
 */
class ConnectDirective
{
    public function compile(string $template): string
    {
        // Replace #[connect] with conditional rendering
        $template = preg_replace_callback(
            '/#\[connect\]/',
            function ($matches) {
                return '<?php if (Bridge::isConnectRequest()): ?>
                    <div id="app" data-page="<?php echo json_encode($__connectData ?? []); ?>"></div>
                <?php else: ?>
                    <!-- Content for non-Connect pages -->
                    <div id="app">
                        <?php echo $__content ?? ""; ?>
                    </div>
                <?php endif; ?>';
            },
            $template
        );
        
        return $template;
    }

    public function extractConnectData(string $template): array
    {
        preg_match_all('/#\[connect\]/', $template, $matches);
        return $matches[0] ?? [];
    }

    public function validateConnect(string $template): bool
    {
        $connectCount = substr_count($template, '#[connect]');
        
        if ($connectCount > 1) {
            throw new \Exception("Multiple #[connect] directives found. Only one is allowed per template.");
        }

        return true;
    }
} 