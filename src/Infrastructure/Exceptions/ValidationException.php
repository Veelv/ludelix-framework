<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Exceptions;

/**
 * Exceção para falhas de validação de arquivos
 * 
 * Especialização da StorageException para casos específicos
 * de falha na validação de arquivos.
 */
class ValidationException extends StorageException
{
    private array $validationErrors;
    private array $validationWarnings;

    public function __construct(
        string $message = '',
        array $validationErrors = [],
        array $validationWarnings = [],
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $path = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, 'validation', $path, $context);
        
        $this->validationErrors = $validationErrors;
        $this->validationWarnings = $validationWarnings;
    }

    /**
     * Cria exceção para tipo MIME inválido
     */
    public static function invalidMimeType(
        string $actualType,
        array $allowedTypes,
        ?string $path = null
    ): self {
        $message = "Tipo MIME inválido '{$actualType}'. Tipos permitidos: " . implode(', ', $allowedTypes);
        
        return new self(
            message: $message,
            validationErrors: ['mime_type' => $message],
            path: $path
        );
    }

    /**
     * Cria exceção para tamanho de arquivo inválido
     */
    public static function invalidFileSize(
        int $actualSize,
        int $maxSize,
        int $minSize = 0,
        ?string $path = null
    ): self {
        $message = "Tamanho de arquivo inválido ({$actualSize} bytes). ";
        $message .= "Tamanho deve estar entre {$minSize} e {$maxSize} bytes.";
        
        return new self(
            message: $message,
            validationErrors: ['file_size' => $message],
            path: $path
        );
    }

    /**
     * Cria exceção para extensão inválida
     */
    public static function invalidExtension(
        string $actualExtension,
        array $allowedExtensions,
        ?string $path = null
    ): self {
        $message = "Extensão inválida '{$actualExtension}'. Extensões permitidas: " . implode(', ', $allowedExtensions);
        
        return new self(
            message: $message,
            validationErrors: ['extension' => $message],
            path: $path
        );
    }

    /**
     * Cria exceção para dimensões de imagem inválidas
     */
    public static function invalidImageDimensions(
        int $width,
        int $height,
        int $maxWidth,
        int $maxHeight,
        int $minWidth = 0,
        int $minHeight = 0,
        ?string $path = null
    ): self {
        $message = "Dimensões de imagem inválidas ({$width}x{$height}). ";
        $message .= "Dimensões devem estar entre {$minWidth}x{$minHeight} e {$maxWidth}x{$maxHeight}.";
        
        return new self(
            message: $message,
            validationErrors: ['image_dimensions' => $message],
            path: $path
        );
    }

    /**
     * Cria exceção para arquivo potencialmente perigoso
     */
    public static function securityThreat(string $reason, ?string $path = null): self
    {
        $message = "Arquivo rejeitado por motivos de segurança: {$reason}";
        
        return new self(
            message: $message,
            validationErrors: ['security' => $message],
            path: $path
        );
    }

    /**
     * Cria exceção com múltiplos erros de validação
     */
    public static function multipleErrors(
        array $validationErrors,
        array $validationWarnings = [],
        ?string $path = null
    ): self {
        $message = "Falha na validação: " . implode('; ', $validationErrors);
        
        return new self(
            message: $message,
            validationErrors: $validationErrors,
            validationWarnings: $validationWarnings,
            path: $path
        );
    }

    /**
     * Obtém os erros de validação
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Obtém os avisos de validação
     */
    public function getValidationWarnings(): array
    {
        return $this->validationWarnings;
    }

    /**
     * Verifica se há erros de validação
     */
    public function hasValidationErrors(): bool
    {
        return !empty($this->validationErrors);
    }

    /**
     * Verifica se há avisos de validação
     */
    public function hasValidationWarnings(): bool
    {
        return !empty($this->validationWarnings);
    }

    /**
     * Converte a exceção para array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'validation_errors' => $this->validationErrors,
            'validation_warnings' => $this->validationWarnings,
        ]);
    }
}

