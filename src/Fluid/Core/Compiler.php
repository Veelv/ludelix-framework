<?php

namespace Ludelix\Fluid\Core;

use Ludelix\Fluid\Utilities\UtilityInterface;

class Compiler
{
    private Config $config;
    private array $utilities;

    public function __construct(Config $config, array $utilities)
    {
        $this->config = $config;
        $this->utilities = $utilities;
    }

    /**
     * Valida se uma utility existe e está registrada
     */
    private function validateUtility(string $utilityName): bool
    {
        foreach ($this->utilities as $utility) {
            $styles = $utility::getStyles();
            if (isset($styles[$utilityName])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Processa uma classe Fluid e retorna a classe base
     */
    private function processFluidClass(string $class): ?string
    {
        // Não é uma classe Fluid
        if (strpos($class, 'fl-') !== 0) {
            return $class;
        }

        // Remove o prefixo fl-
        $utilityName = substr($class, 3);

        // Processa variantes (dark:, hover:, etc)
        if (strpos($utilityName, ':') !== false) {
            [$variant, $utility] = explode(':', $utilityName, 2);
            if ($this->validateUtility('fl-' . $utility)) {
                return $variant . ':' . $utility;
            }
        } else {
            if ($this->validateUtility('fl-' . $utilityName)) {
                return $utilityName;
            }
        }

        return null;
    }

    /**
     * Converte diretivas Fluid em classes base, mantendo apenas as classes válidas
     */
    public function parseFluidClasses(string $classString): string
    {
        $classes = array_filter(array_map('trim', explode('|', $classString)));
        $processedClasses = [];

        foreach ($classes as $class) {
            $processedClass = $this->processFluidClass($class);
            if ($processedClass !== null) {
                $processedClasses[] = $processedClass;
            }
        }

        return implode(' ', array_unique($processedClasses));
    }
}
