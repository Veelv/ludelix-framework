<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Contracts;

use Ludelix\Infrastructure\ValueObjects\ValidationResult;
use Ludelix\PRT\UploadedFile;

/**
 * Interface para validação de arquivos
 * 
 * Define o contrato para validadores de arquivo, permitindo extensibilidade
 * através de regras customizadas e validação modular.
 */
interface FileValidatorInterface
{
    /**
     * Valida um arquivo contra um conjunto de regras
     *
     * @param UploadedFile $file Arquivo a ser validado
     * @param array $rules Regras de validação a serem aplicadas
     * @return ValidationResult Resultado da validação
     */
    public function validate(UploadedFile $file, array $rules = []): ValidationResult;

    /**
     * Adiciona uma regra de validação customizada
     *
     * @param string $name Nome da regra
     * @param callable $rule Função de validação
     * @return void
     */
    public function addRule(string $name, callable $rule): void;

    /**
     * Remove uma regra de validação
     *
     * @param string $name Nome da regra a ser removida
     * @return void
     */
    public function removeRule(string $name): void;

    /**
     * Obtém todas as regras registradas
     *
     * @return array Lista de regras disponíveis
     */
    public function getRules(): array;

    /**
     * Valida tipo MIME do arquivo
     *
     * @param UploadedFile $file Arquivo a ser validado
     * @param array $allowedTypes Tipos MIME permitidos
     * @return bool True se válido
     */
    public function validateMimeType(UploadedFile $file, array $allowedTypes): bool;

    /**
     * Valida tamanho do arquivo
     *
     * @param UploadedFile $file Arquivo a ser validado
     * @param int $maxSize Tamanho máximo em bytes
     * @param int $minSize Tamanho mínimo em bytes
     * @return bool True se válido
     */
    public function validateSize(UploadedFile $file, int $maxSize, int $minSize = 0): bool;

    /**
     * Valida extensão do arquivo
     *
     * @param UploadedFile $file Arquivo a ser validado
     * @param array $allowedExtensions Extensões permitidas
     * @return bool True se válido
     */
    public function validateExtension(UploadedFile $file, array $allowedExtensions): bool;

    /**
     * Valida dimensões de imagem
     *
     * @param UploadedFile $file Arquivo de imagem
     * @param int $maxWidth Largura máxima
     * @param int $maxHeight Altura máxima
     * @param int $minWidth Largura mínima
     * @param int $minHeight Altura mínima
     * @return bool True se válido
     */
    public function validateImageDimensions(
        UploadedFile $file,
        int $maxWidth = 0,
        int $maxHeight = 0,
        int $minWidth = 0,
        int $minHeight = 0
    ): bool;

    /**
     * Realiza análise de segurança do arquivo
     *
     * @param UploadedFile $file Arquivo a ser analisado
     * @return bool True se seguro
     */
    public function validateSecurity(UploadedFile $file): bool;
}

