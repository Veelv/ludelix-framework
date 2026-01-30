<?php

namespace Ludelix\Fluid\Core;

use Ludelix\Fluid\Utilities\UtilityInterface;

class Generator
{
    private Config $config;
    private array $utilities;
    private array $usedClasses = [];

    public function __construct(Config $config, array $utilities = [])
    {
        $this->config = $config;
        $this->utilities = $utilities;

        // Se nenhuma utility foi fornecida, registra as padrão
        if (empty($this->utilities)) {
            $this->registerDefaultUtilities();
        }
    }

    /**
     * Registra as utilities padrão do Fluid
     */
    private function registerDefaultUtilities(): void
    {
        $this->utilities = $this->config->getUtilities();
    }

    /**
     * Registra uma classe como usada
     */
    public function registerClass(string $class): void
    {
        $this->usedClasses[$class] = true;
    }

    /**
     * Registra múltiplas classes como usadas
     */
    public function registerClasses(array $classes): void
    {
        foreach ($classes as $class) {
            $this->registerClass($class);
        }
    }

    /**
     * Gera CSS para as classes registradas
     */
    public function generateCSS(): string
    {
        $css = '';
        $allStyles = $this->getAllUtilityStyles();

        foreach ($this->usedClasses as $class => $used) {
            if (isset($allStyles[$class])) {
                $css .= $this->generateClassCSS($class, $allStyles[$class]);
            }
        }

        return $css;
    }

    /**
     * Obtém todos os estilos de todas as utilities
     */
    protected function getAllUtilityStyles(): array
    {
        $allStyles = [];

        foreach ($this->utilities as $utility) {
            if (is_string($utility) && class_exists($utility)) {
                $styles = $utility::getStyles();
                $allStyles = array_merge($allStyles, $styles);
            } elseif (is_object($utility) && method_exists($utility, 'getStyles')) {
                $styles = $utility->getStyles();
                $allStyles = array_merge($allStyles, $styles);
            }
        }

        return $allStyles;
    }

    /**
     * Gera CSS para uma classe específica
     */
    protected function generateClassCSS(string $class, array $styles): string
    {
        $css = ".{$class} {\n";

        foreach ($styles as $property => $value) {
            if (is_array($value)) {
                continue; // Skip array values to prevent array to string conversion error
            }
            $css .= "  {$property}: {$value};\n";
        }

        $css .= "}\n\n";

        return $css;
    }

    /**
     * Extrai classes fl-* do HTML
     */
    public function extractFluidClasses(string $html): array
    {
        preg_match_all('/class="([^"]*fl-[^"]*)"/', $html, $matches);

        $fluidClasses = [];
        foreach ($matches[1] as $classString) {
            $classes = explode(' ', $classString);
            foreach ($classes as $class) {
                $class = trim($class);
                if (str_starts_with($class, 'fl-')) {
                    $fluidClasses[] = $class;
                }
            }
        }

        return array_unique($fluidClasses);
    }

    /**
     * Processa HTML e injeta CSS
     */
    public function processHTML(string $html): string
    {
        $fluidClasses = $this->extractFluidClasses($html);

        if (empty($fluidClasses)) {
            return $html;
        }

        // Registra as classes encontradas
        $this->registerClasses($fluidClasses);

        // Gera o CSS
        $css = $this->generateCSS();

        if (empty($css)) {
            return $html;
        }

        // Adiciona comentário para debug
        $cssWithComments = "/* Fluid CSS - Generated dynamically */\n" . $css . "/* End Fluid CSS */\n";

        // Injeta o CSS no HTML
        if (str_contains($html, '</head>')) {
            $html = str_replace('</head>', "<style data-fluid>\n{$cssWithComments}</style>\n</head>", $html);
        } elseif (str_contains($html, '</body>')) {
            $html = str_replace('</body>', "<style data-fluid>\n{$cssWithComments}</style>\n</body>", $html);
        } else {
            $html = "<style data-fluid>\n{$cssWithComments}</style>\n" . $html;
        }

        // Injeta o Javascript padrão do Fluid
        $jsPath = dirname(__DIR__) . '/Assets/js/fluid.js';
        if (file_exists($jsPath)) {
            $jsContent = file_get_contents($jsPath);
            $script = "<script id=\"fluid-js\">\n{$jsContent}\n</script>";

            if (str_contains($html, '</body>')) {
                $html = str_replace('</body>', "{$script}\n</body>", $html);
            } else {
                $html .= "\n{$script}";
            }
        }

        return $html;
    }

    /**
     * Limpa as classes registradas
     */
    public function clearUsedClasses(): void
    {
        $this->usedClasses = [];
    }

    /**
     * Obtém as classes registradas
     */
    public function getUsedClasses(): array
    {
        return array_keys($this->usedClasses);
    }
}