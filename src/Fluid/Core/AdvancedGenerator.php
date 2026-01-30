<?php

namespace Ludelix\Fluid\Core;

class AdvancedGenerator extends Generator
{
    private AdvancedParser $parser;

    public function __construct(Config $config, array $utilities = [])
    {
        parent::__construct($config, $utilities);
        $this->parser = new AdvancedParser();
    }

    /**
     * Gera CSS para as classes registradas com suporte a modificadores
     */
    public function generateCSS(): string
    {
        $css = '';
        $allStyles = $this->getAllUtilityStyles();

        foreach ($this->getUsedClasses() as $class) {
            if ($this->parser->needsAdvancedProcessing($class)) {
                // Para classes com modificadores, busca os estilos da classe base
                $normalizedClass = $this->parser->normalizeClass($class);
                $parsed = $this->parser->parseClass($normalizedClass);
                $baseClass = 'fl-' . $parsed['base_class'];
                
                if (isset($allStyles[$baseClass])) {
                    $css .= $this->generateAdvancedClassCSS($class, $allStyles[$baseClass]);
                }
            } else {
                // Classes normais
                if (isset($allStyles[$class])) {
                    $css .= $this->generateClassCSS($class, $allStyles[$class]);
                }
            }
        }

        return $css;
    }

    /**
     * Gera CSS para uma classe específica com suporte a modificadores
     */
    protected function generateClassCSS(string $class, array $styles): string
    {
        // Se a classe precisa de processamento avançado
        if ($this->parser->needsAdvancedProcessing($class)) {
            return $this->generateAdvancedClassCSS($class, $styles);
        }

        // Geração padrão
        return parent::generateClassCSS($class, $styles);
    }

    /**
     * Gera CSS avançado com modificadores
     */
    private function generateAdvancedClassCSS(string $originalClass, array $styles): string
    {
        $normalizedClass = $this->parser->normalizeClass($originalClass);
        $parsed = $this->parser->parseClass($normalizedClass);
        
        // Gera o seletor CSS baseado no parsing
        $selector = $this->parser->generateSelector($originalClass, $parsed);
        
        // Gera media query se necessário
        $mediaQuery = $this->parser->generateMediaQuery($parsed);
        
        // Processa os estilos
        $processedStyles = $this->processStyles($styles, $parsed);
        
        // Gera o CSS
        $css = $this->buildCSSRule($selector, $processedStyles, $mediaQuery);
        
        return $css;
    }

    /**
     * Processa os estilos baseado no parsing (valores negativos, etc.)
     */
    private function processStyles(array $styles, array $parsed): array
    {
        if ($parsed['is_negative']) {
            return $this->applyNegativeValues($styles);
        }
        
        return $styles;
    }

    /**
     * Aplica valores negativos aos estilos
     */
    private function applyNegativeValues(array $styles): array
    {
        $negativeStyles = [];
        
        foreach ($styles as $property => $value) {
            // Propriedades que suportam valores negativos
            if (in_array($property, ['margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left', 
                                   'margin-inline-start', 'margin-inline-end', 'top', 'right', 'bottom', 'left',
                                   'inset', 'translate', 'transform-origin'])) {
                
                if (is_numeric($value) || (is_string($value) && str_ends_with($value, 'px') || str_ends_with($value, 'rem') || str_ends_with($value, 'em'))) {
                    $negativeStyles[$property] = $this->negateValue($value);
                } else {
                    $negativeStyles[$property] = $value;
                }
            } else {
                $negativeStyles[$property] = $value;
            }
        }
        
        return $negativeStyles;
    }

    /**
     * Nega um valor CSS
     */
    private function negateValue(string $value): string
    {
        if (str_starts_with($value, '-')) {
            return substr($value, 1); // Remove o - se já existe
        }
        
        if ($value === '0' || $value === '0px') {
            return $value; // 0 não pode ser negativo
        }
        
        return '-' . $value;
    }

    /**
     * Constrói uma regra CSS completa
     */
    private function buildCSSRule(string $selector, array $styles, ?string $mediaQuery = null): string
    {
        $css = '';
        
        // Se tem estilos nested (como space-between)
        $hasNested = false;
        $regularStyles = [];
        $nestedRules = [];
        
        foreach ($styles as $property => $value) {
            if (is_array($value)) {
                $hasNested = true;
                $nestedRules[$property] = $value;
            } else {
                $regularStyles[$property] = $value;
            }
        }
        
        // Gera estilos regulares
        if (!empty($regularStyles)) {
            $styleDeclarations = [];
            foreach ($regularStyles as $property => $value) {
                $styleDeclarations[] = "  {$property}: {$value};";
            }
            
            $ruleCSS = "{$selector} {\n" . implode("\n", $styleDeclarations) . "\n}\n\n";
            
            if ($mediaQuery) {
                $css .= "{$mediaQuery} {\n  {$ruleCSS}}\n\n";
            } else {
                $css .= $ruleCSS;
            }
        }
        
        // Gera regras nested
        foreach ($nestedRules as $nestedSelector => $nestedStyles) {
            $nestedStyleDeclarations = [];
            foreach ($nestedStyles as $property => $value) {
                $nestedStyleDeclarations[] = "  {$property}: {$value};";
            }
            
            $fullNestedSelector = "{$selector} {$nestedSelector}";
            $nestedRuleCSS = "{$fullNestedSelector} {\n" . implode("\n", $nestedStyleDeclarations) . "\n}\n\n";
            
            if ($mediaQuery) {
                $css .= "{$mediaQuery} {\n  {$nestedRuleCSS}}\n\n";
            } else {
                $css .= $nestedRuleCSS;
            }
        }
        
        return $css;
    }

    /**
     * Extrai classes Fluid do HTML com suporte a modificadores e sintaxe de atributos #[]
     */
    public function extractFluidClasses(string $html): array
    {
        $fluidClasses = [];
        
        // Extrai classes de atributos class="..."
        preg_match_all('/class="([^"]*)"/', $html, $classMatches);
        foreach ($classMatches[1] as $classString) {
            $classes = explode(' ', $classString);
            foreach ($classes as $class) {
                $class = trim($class);
                if (str_starts_with($class, 'fl-') || str_starts_with($class, 'fl--')) {
                    $fluidClasses[] = $class;
                }
            }
        }
        
        // Extrai classes de atributos #[] (sintaxe do Ludou)
        preg_match_all('/#\[([^\]]+)\]/', $html, $attrMatches);
        foreach ($attrMatches[1] as $attrString) {
            $parts = explode('|', $attrString);
            foreach ($parts as $part) {
                $part = trim($part);
                if (str_starts_with($part, 'fl-') || str_starts_with($part, 'fl--')) {
                    $fluidClasses[] = $part;
                    
                    // Se tiver pseudo-classes, também adiciona a versão base
                    if (str_contains($part, ':')) {
                        $baseClass = explode(':', $part)[1];
                        if (str_starts_with($baseClass, 'fl-')) {
                            $fluidClasses[] = $baseClass;
                        }
                    }
                }
            }
        }
        
        return array_unique($fluidClasses);
    }

    /**
     * Registra múltiplas classes incluindo suas variações com modificadores
     */
    public function registerClasses(array $classes): void
    {
        foreach ($classes as $class) {
            // Registra a classe original
            parent::registerClass($class);
            
            // Se é uma classe com modificador, também registra a base
            if ($this->parser->needsAdvancedProcessing($class)) {
                $parsed = $this->parser->parseClass($this->parser->normalizeClass($class));
                parent::registerClass('fl-' . $parsed['base_class']);
            }
        }
    }
}