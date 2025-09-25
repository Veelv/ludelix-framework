<?php

namespace Ludelix\Fluid\Core;

/**
 * Sistema de Responsividade Integrado ao Fuid
 * 
 * Implementa uma abordagem mobile-first com breakpoints configuráveis
 * e geração automática de variantes responsivas para todas as utilities.
 */
class ResponsiveSystem
{
    private array $breakpoints;
    private array $config;
    private string $prefix;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? 'fl-';
        $this->initializeBreakpoints();
    }

    /**
     * Inicializa os breakpoints padrão ou personalizados
     */
    private function initializeBreakpoints(): void
    {
        $this->breakpoints = $this->config['breakpoints'] ?? [
            'sm' => '640px',
            'md' => '768px',
            'lg' => '1024px',
            'xl' => '1280px',
            '2xl' => '1536px',
        ];
    }

    /**
     * Gera variantes responsivas para uma utility específica
     */
    public function generateResponsiveVariants(string $utility, array $styles): array
    {
        $variants = [];
        
        // Utility base (mobile-first)
        $variants[$utility] = $styles;
        
        // Gerar variantes para cada breakpoint
        foreach ($this->breakpoints as $breakpoint => $minWidth) {
            $responsiveUtility = "{$breakpoint}:{$utility}";
            $variants[$responsiveUtility] = [
                'media' => "(min-width: {$minWidth})",
                'styles' => $styles,
            ];
        }
        
        return $variants;
    }

    /**
     * Processa uma classe responsiva e retorna informações sobre ela
     */
    public function parseResponsiveClass(string $class): array
    {
        // Remover prefixo fl- se presente
        if (strpos($class, $this->prefix) === 0) {
            $class = substr($class, strlen($this->prefix));
        }

        // Verificar se é uma classe responsiva
        if (strpos($class, ':') !== false) {
            [$breakpoint, $utility] = explode(':', $class, 2);
            
            if (isset($this->breakpoints[$breakpoint])) {
                return [
                    'is_responsive' => true,
                    'breakpoint' => $breakpoint,
                    'min_width' => $this->breakpoints[$breakpoint],
                    'utility' => $utility,
                    'full_class' => $this->prefix . $class,
                ];
            }
        }

        return [
            'is_responsive' => false,
            'breakpoint' => null,
            'min_width' => null,
            'utility' => $class,
            'full_class' => $this->prefix . $class,
        ];
    }

    /**
     * Gera CSS para uma classe responsiva
     */
    public function generateResponsiveCss(string $class, array $styles): string
    {
        $parsed = $this->parseResponsiveClass($class);
        $css = '';

        if ($parsed['is_responsive']) {
            // Gerar CSS com media query
            $selector = ".{$parsed['full_class']}";
            $mediaQuery = "@media (min-width: {$parsed['min_width']})";
            
            $css .= "{$mediaQuery} {\n";
            $css .= "  {$selector} {\n";
            
            foreach ($styles as $property => $value) {
                $css .= "    {$property}: {$value};\n";
            }
            
            $css .= "  }\n";
            $css .= "}\n";
        } else {
            // Gerar CSS normal (mobile-first)
            $selector = ".{$parsed['full_class']}";
            $css .= "{$selector} {\n";
            
            foreach ($styles as $property => $value) {
                $css .= "  {$property}: {$value};\n";
            }
            
            $css .= "}\n";
        }

        return $css;
    }

    /**
     * Valida se um breakpoint existe
     */
    public function isValidBreakpoint(string $breakpoint): bool
    {
        return isset($this->breakpoints[$breakpoint]);
    }

    /**
     * Obtém todos os breakpoints configurados
     */
    public function getBreakpoints(): array
    {
        return $this->breakpoints;
    }

    /**
     * Adiciona um novo breakpoint
     */
    public function addBreakpoint(string $name, string $minWidth): void
    {
        $this->breakpoints[$name] = $minWidth;
    }

    /**
     * Remove um breakpoint
     */
    public function removeBreakpoint(string $name): void
    {
        unset($this->breakpoints[$name]);
    }

    /**
     * Gera todas as variantes responsivas para um conjunto de utilities
     */
    public function generateAllResponsiveVariants(array $utilities): array
    {
        $allVariants = [];

        foreach ($utilities as $utility => $styles) {
            $variants = $this->generateResponsiveVariants($utility, $styles);
            $allVariants = array_merge($allVariants, $variants);
        }

        return $allVariants;
    }

    /**
     * Extrai classes responsivas de um conjunto de classes
     */
    public function extractResponsiveClasses(array $classes): array
    {
        $responsive = [];
        $nonResponsive = [];

        foreach ($classes as $class) {
            $parsed = $this->parseResponsiveClass($class);
            
            if ($parsed['is_responsive']) {
                $responsive[] = $parsed;
            } else {
                $nonResponsive[] = $parsed;
            }
        }

        return [
            'responsive' => $responsive,
            'non_responsive' => $nonResponsive,
        ];
    }

    /**
     * Ordena classes responsivas por ordem de breakpoint
     */
    public function sortResponsiveClasses(array $responsiveClasses): array
    {
        $breakpointOrder = array_keys($this->breakpoints);
        
        usort($responsiveClasses, function($a, $b) use ($breakpointOrder) {
            $aIndex = array_search($a['breakpoint'], $breakpointOrder);
            $bIndex = array_search($b['breakpoint'], $breakpointOrder);
            
            return $aIndex <=> $bIndex;
        });

        return $responsiveClasses;
    }

    /**
     * Gera CSS otimizado para múltiplas classes responsivas
     */
    public function generateOptimizedResponsiveCss(array $classes): string
    {
        $extracted = $this->extractResponsiveClasses($classes);
        $css = '';

        // CSS para classes não responsivas (mobile-first)
        foreach ($extracted['non_responsive'] as $classInfo) {
            // Aqui você integraria com o sistema de utilities para obter os estilos
            // Por enquanto, vamos simular
            $styles = $this->getStylesForUtility($classInfo['utility']);
            if ($styles) {
                $css .= $this->generateResponsiveCss($classInfo['utility'], $styles);
            }
        }

        // Agrupar classes responsivas por breakpoint
        $groupedByBreakpoint = [];
        foreach ($extracted['responsive'] as $classInfo) {
            $breakpoint = $classInfo['breakpoint'];
            if (!isset($groupedByBreakpoint[$breakpoint])) {
                $groupedByBreakpoint[$breakpoint] = [];
            }
            $groupedByBreakpoint[$breakpoint][] = $classInfo;
        }

        // Gerar CSS agrupado por media query
        foreach ($this->breakpoints as $breakpoint => $minWidth) {
            if (isset($groupedByBreakpoint[$breakpoint])) {
                $css .= "@media (min-width: {$minWidth}) {\n";
                
                foreach ($groupedByBreakpoint[$breakpoint] as $classInfo) {
                    $styles = $this->getStylesForUtility($classInfo['utility']);
                    if ($styles) {
                        $selector = ".{$classInfo['full_class']}";
                        $css .= "  {$selector} {\n";
                        
                        foreach ($styles as $property => $value) {
                            $css .= "    {$property}: {$value};\n";
                        }
                        
                        $css .= "  }\n";
                    }
                }
                
                $css .= "}\n";
            }
        }

        return $css;
    }

    /**
     * Método auxiliar para obter estilos de uma utility
     * (Seria integrado com o sistema de utilities real)
     */
    private function getStylesForUtility(string $utility): ?array
    {
        // Simulação - na implementação real, isso consultaria o sistema de utilities
        $mockStyles = [
            'text-lg' => ['font-size' => '1.125rem', 'line-height' => '1.75rem'],
            'flex' => ['display' => 'flex'],
            'flex-col' => ['flex-direction' => 'column'],
            'w-full' => ['width' => '100%'],
            'w-1/2' => ['width' => '50%'],
            'grid' => ['display' => 'grid'],
            'grid-cols-2' => ['grid-template-columns' => 'repeat(2, minmax(0, 1fr))'],
            'gap-4' => ['gap' => '1rem'],
        ];

        return $mockStyles[$utility] ?? null;
    }

    /**
     * Valida se uma classe responsiva está bem formada
     */
    public function validateResponsiveClass(string $class): array
    {
        $parsed = $this->parseResponsiveClass($class);
        $errors = [];

        if ($parsed['is_responsive']) {
            if (!$this->isValidBreakpoint($parsed['breakpoint'])) {
                $errors[] = "Breakpoint '{$parsed['breakpoint']}' não é válido";
            }
        }

        if (empty($parsed['utility'])) {
            $errors[] = "Utility não pode estar vazia";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'parsed' => $parsed,
        ];
    }

    /**
     * Gera documentação dos breakpoints
     */
    public function generateBreakpointDocumentation(): array
    {
        $docs = [];
        
        foreach ($this->breakpoints as $name => $minWidth) {
            $docs[] = [
                'name' => $name,
                'min_width' => $minWidth,
                'prefix' => "{$name}:",
                'example' => "{$name}:fl-text-lg",
                'description' => "Aplica estilos em telas de {$minWidth} e acima",
            ];
        }

        return $docs;
    }

    /**
     * Obtém estatísticas do sistema responsivo
     */
    public function getStats(): array
    {
        return [
            'total_breakpoints' => count($this->breakpoints),
            'breakpoint_names' => array_keys($this->breakpoints),
            'smallest_breakpoint' => reset($this->breakpoints),
            'largest_breakpoint' => end($this->breakpoints),
            'prefix' => $this->prefix,
            'mobile_first' => true,
        ];
    }
}
