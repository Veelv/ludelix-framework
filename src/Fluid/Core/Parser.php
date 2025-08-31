<?php

namespace Ludelix\Fluid\Core;

use Ludelix\Core\Config;

class Parser
{
    private Config $config;
    private array $parsedClasses = [];
    private array $usedClasses = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function parse(string $content): string
    {
        if (strpos($content, '#[') === false) {
            return $content;
        }

        // Extrai classes do formato #[fl-class1|fl-class2]
        $classes = [];
        if (preg_match('/#\[(.*?)\]/', $content, $matches)) {
            $classes = explode('|', $matches[1]);
        }

        $processedClasses = [];
        foreach ($classes as $class) {
            $class = trim($class);
            if (empty($class)) continue;

            // Se começar com fl-, é uma classe Fluid - remove o prefixo
            if (strpos($class, 'fl-') === 0) {
                $this->usedClasses[$class] = true;
                // Remove o prefixo fl- e adiciona a classe base
                $processedClasses[] = substr($class, 3);
            } else {
                $processedClasses[] = $class;
            }
        }

        // Retorna a string class="..." com as classes processadas
        return 'class="' . htmlspecialchars(implode(' ', $processedClasses)) . '"';
    }
    
    public function getUsedClasses(): array
    {
        return array_keys($this->usedClasses);
    }
    
    /**
     * Register a class to track its usage for CSS generation
     */
    public function registerClass(string $class): void
    {
        $this->usedClasses[$class] = true;
    }
    
    private function processVariants(string $class): string
    {
        $parts = explode(':', $class, 2);
        
        // Check for responsive or pseudo-class variants
        if (count($parts) === 2) {
            $prefix = $parts[0];
            $class = $parts[1];
            
            // Handle responsive variants (e.g., md:fl-p-4)
            if (in_array($prefix, $this->config->get('ripples.responsive', []))) {
                return $this->processResponsiveVariant($prefix, $class);
            }
            
            // Handle pseudo-class variants (e.g., hover:fl-bg-primary)
            if (in_array($prefix, $this->config->get('ripples.pseudo', []))) {
                return $this->processPseudoVariant($prefix, $class);
            }
            
            // Handle dark mode
            if ($prefix === 'dark' && $this->config->get('ripples.dark', false)) {
                return $this->processDarkVariant($class);
            }
        }
        
        // Process regular class
        return $this->processUtilityClass($class);
    }
    
    private function processResponsiveVariant(string $breakpoint, string $class): string
    {
        $utility = $this->processUtilityClass($class);
        return "{$breakpoint}:{$utility}";
    }
    
    private function processPseudoVariant(string $pseudo, string $class): string
    {
        $utility = $this->processUtilityClass($class);
        return "{$pseudo}:{$utility}";
    }
    
    private function processDarkVariant(string $class): string
    {
        $utility = $this->processUtilityClass($class);
        return "dark:{$utility}";
    }
    
    private function processUtilityClass(string $class): string
    {
        // Check if class has a value (e.g., fl-p-4)
        if (preg_match('/^(fl-[a-z]+)-([a-z0-9.-]+)$/', $class, $matches)) {
            $prefix = $matches[1];
            $value = $matches[2];
            
            // Track the base class (without value) for purging
            $this->trackClassUsage($prefix);
            
            // Check if the value is a theme variable
            if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $themeVar = trim($value, '[]');
                return "{$prefix}-[{$themeVar}]";
            }
            
            return $class;
        }
        
        // Track the class for purging
        $this->trackClassUsage($class);
        
        return $class;
    }
    
    private function trackClassUsage(string $class): void
    {
        // Remove any variants to track the base class
        $baseClass = preg_replace('/^(?:[a-z]+:)*([^:]+)/', '$1', $class);
        $this->usedClasses[$baseClass] = true;
    }
}
