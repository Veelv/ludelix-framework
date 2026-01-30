<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\ValueObjects;

/**
 * Representa o resultado de uma validação de arquivo
 * 
 * Objeto de valor imutável que encapsula o resultado de validações
 * aplicadas a um arquivo, incluindo erros e avisos.
 */
readonly class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = [],
        public array $passedRules = [],
        public array $metadata = []
    ) {}

    /**
     * Cria um resultado de validação bem-sucedida
     */
    public static function valid(array $passedRules = [], array $warnings = [], array $metadata = []): self
    {
        return new self(
            isValid: true,
            errors: [],
            warnings: $warnings,
            passedRules: $passedRules,
            metadata: $metadata
        );
    }

    /**
     * Cria um resultado de validação com falha
     */
    public static function invalid(array $errors, array $warnings = [], array $passedRules = []): self
    {
        return new self(
            isValid: false,
            errors: $errors,
            warnings: $warnings,
            passedRules: $passedRules
        );
    }

    /**
     * Adiciona um erro ao resultado
     */
    public function withError(string $rule, string $message): self
    {
        $errors = $this->errors;
        $errors[$rule] = $message;

        return new self(
            isValid: false,
            errors: $errors,
            warnings: $this->warnings,
            passedRules: $this->passedRules,
            metadata: $this->metadata
        );
    }

    /**
     * Adiciona um aviso ao resultado
     */
    public function withWarning(string $rule, string $message): self
    {
        $warnings = $this->warnings;
        $warnings[$rule] = $message;

        return new self(
            isValid: $this->isValid,
            errors: $this->errors,
            warnings: $warnings,
            passedRules: $this->passedRules,
            metadata: $this->metadata
        );
    }

    /**
     * Verifica se há erros
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Verifica se há avisos
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Obtém todos os erros como string
     */
    public function getErrorsAsString(string $separator = '; '): string
    {
        return implode($separator, $this->errors);
    }

    /**
     * Obtém todos os avisos como string
     */
    public function getWarningsAsString(string $separator = '; '): string
    {
        return implode($separator, $this->warnings);
    }

    /**
     * Combina com outro resultado de validação
     */
    public function merge(ValidationResult $other): self
    {
        return new self(
            isValid: $this->isValid && $other->isValid,
            errors: array_merge($this->errors, $other->errors),
            warnings: array_merge($this->warnings, $other->warnings),
            passedRules: array_merge($this->passedRules, $other->passedRules),
            metadata: array_merge($this->metadata, $other->metadata)
        );
    }

    /**
     * Converte para array
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'passed_rules' => $this->passedRules,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Converte para JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

