<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Core;

use Ludelix\Infrastructure\Contracts\FileValidatorInterface;
use Ludelix\Infrastructure\ValueObjects\ValidationResult;
use Ludelix\Infrastructure\Exceptions\ValidationException;
use Ludelix\PRT\UploadedFile;

/**
 * Implementação do validador de arquivos
 * 
 * Fornece validação abrangente de arquivos incluindo tipo MIME,
 * tamanho, extensão, dimensões de imagem e análise de segurança.
 */
class FileValidator implements FileValidatorInterface
{
    private array $rules = [];
    private array $defaultRules = [];

    public function __construct()
    {
        $this->initializeDefaultRules();
    }

    public function validate(UploadedFile $file, array $rules = []): ValidationResult
    {
        $rulesToApply = array_merge($this->defaultRules, $rules);
        $errors = [];
        $warnings = [];
        $passedRules = [];

        foreach ($rulesToApply as $ruleName => $ruleConfig) {
            try {
                $result = $this->applyRule($file, $ruleName, $ruleConfig);
                
                if ($result === true) {
                    $passedRules[] = $ruleName;
                } elseif (is_array($result)) {
                    if (isset($result['error'])) {
                        $errors[$ruleName] = $result['error'];
                    }
                    if (isset($result['warning'])) {
                        $warnings[$ruleName] = $result['warning'];
                    }
                    if ($result['passed'] ?? false) {
                        $passedRules[] = $ruleName;
                    }
                }
            } catch (\Exception $e) {
                $errors[$ruleName] = "Erro na validação: " . $e->getMessage();
            }
        }

        $isValid = empty($errors);

        return new ValidationResult(
            isValid: $isValid,
            errors: $errors,
            warnings: $warnings,
            passedRules: $passedRules
        );
    }

    public function addRule(string $name, callable $rule): void
    {
        $this->rules[$name] = $rule;
    }

    public function removeRule(string $name): void
    {
        unset($this->rules[$name]);
        unset($this->defaultRules[$name]);
    }

    public function getRules(): array
    {
        return array_merge($this->defaultRules, $this->rules);
    }

    public function validateMimeType(UploadedFile $file, array $allowedTypes): bool
    {
        $actualType = $file->getMimeType();
        $detectedType = $this->detectRealMimeType($file);

        // Verificar se o tipo declarado está na lista permitida
        if (!in_array($actualType, $allowedTypes)) {
            return false;
        }

        // Verificar se o tipo detectado corresponde ao declarado
        if ($detectedType !== $actualType) {
            return false;
        }

        return true;
    }

    public function validateSize(UploadedFile $file, int $maxSize, int $minSize = 0): bool
    {
        $size = $file->getSize();
        
        if ($size < $minSize || $size > $maxSize) {
            return false;
        }

        return true;
    }

    public function validateExtension(UploadedFile $file, array $allowedExtensions): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = array_map('strtolower', $allowedExtensions);

        return in_array($extension, $allowedExtensions);
    }

    public function validateImageDimensions(
        UploadedFile $file,
        int $maxWidth = 0,
        int $maxHeight = 0,
        int $minWidth = 0,
        int $minHeight = 0
    ): bool {
        if (!$this->isImage($file)) {
            return false;
        }

        $imageInfo = getimagesize($file->getPathname());
        if (!$imageInfo) {
            return false;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        if ($minWidth > 0 && $width < $minWidth) {
            return false;
        }

        if ($minHeight > 0 && $height < $minHeight) {
            return false;
        }

        if ($maxWidth > 0 && $width > $maxWidth) {
            return false;
        }

        if ($maxHeight > 0 && $height > $maxHeight) {
            return false;
        }

        return true;
    }

    public function validateSecurity(UploadedFile $file): bool
    {
        // Verificar se o arquivo não é executável
        if ($this->isExecutableFile($file)) {
            $this->logSecurityViolation($file, 'executable_file');
            return false;
        }

        // Verificar magic numbers
        if (!$this->validateMagicNumbers($file)) {
            $this->logSecurityViolation($file, 'invalid_magic_numbers');
            return false;
        }

        // Verificar conteúdo suspeito
        if ($this->hasSuspiciousContent($file)) {
            $this->logSecurityViolation($file, 'suspicious_content');
            return false;
        }

        // Verificar se não é um arquivo PHP disfarçado
        if ($this->isPHPFile($file)) {
            $this->logSecurityViolation($file, 'php_file_upload');
            return false;
        }

        return true;
    }

    /**
     * Registra violações de segurança
     */
    private function logSecurityViolation(UploadedFile $file, string $violationType): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'violation_type' => $violationType,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ];

        $logMessage = json_encode($logData);
        error_log("[SECURITY_VIOLATION] {$logMessage}");
        
        // Também salvar em arquivo específico
        $logFile = cubby_path('logs/security_violations.log');
        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        // Usar SecurityLogger se disponível
        if (class_exists('\Ludelix\Security\Logging\SecurityLogger')) {
            try {
                $logger = new \Ludelix\Security\Logging\SecurityLogger();
                $logger->logUploadViolation($violationType, [
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'extension' => $file->getClientOriginalExtension(),
                ]);
            } catch (\Exception $e) {
                // Fallback para log básico se SecurityLogger falhar
                error_log("SecurityLogger error: " . $e->getMessage());
            }
        }
    }

    /**
     * Inicializa regras padrão de validação
     */
    private function initializeDefaultRules(): void
    {
        $this->defaultRules = [
            'required' => true,
            'security' => true,
        ];
    }

    /**
     * Aplica uma regra específica ao arquivo
     */
    private function applyRule(UploadedFile $file, string $ruleName, $ruleConfig): mixed
    {
        switch ($ruleName) {
            case 'required':
                return $file->isValid();

            case 'mime_types':
                return $this->validateMimeType($file, (array) $ruleConfig);

            case 'extensions':
                return $this->validateExtension($file, (array) $ruleConfig);

            case 'max_size':
                $maxSize = is_string($ruleConfig) ? $this->parseSize($ruleConfig) : (int) $ruleConfig;
                return $this->validateSize($file, $maxSize);

            case 'min_size':
                $minSize = is_string($ruleConfig) ? $this->parseSize($ruleConfig) : (int) $ruleConfig;
                return $this->validateSize($file, PHP_INT_MAX, $minSize);

            case 'image_dimensions':
                if (!is_array($ruleConfig)) {
                    return false;
                }
                return $this->validateImageDimensions(
                    $file,
                    $ruleConfig['max_width'] ?? 0,
                    $ruleConfig['max_height'] ?? 0,
                    $ruleConfig['min_width'] ?? 0,
                    $ruleConfig['min_height'] ?? 0
                );

            case 'security':
                return $this->validateSecurity($file);

            case 'image':
                return $this->isImage($file);

            case 'video':
                return $this->isVideo($file);

            case 'audio':
                return $this->isAudio($file);

            case 'document':
                return $this->isDocument($file);

            default:
                // Verificar se é uma regra customizada
                if (isset($this->rules[$ruleName])) {
                    return call_user_func($this->rules[$ruleName], $file, $ruleConfig);
                }
                return true;
        }
    }

    /**
     * Detecta o tipo MIME real baseado no conteúdo
     */
    private function detectRealMimeType(UploadedFile $file): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file->getPathname());
        finfo_close($finfo);

        return $mimeType ?: 'application/octet-stream';
    }

    /**
     * Verifica se o arquivo é uma imagem
     */
    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Verifica se o arquivo é um vídeo
     */
    private function isVideo(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'video/');
    }

    /**
     * Verifica se o arquivo é um áudio
     */
    private function isAudio(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'audio/');
    }

    /**
     * Verifica se o arquivo é um documento
     */
    private function isDocument(UploadedFile $file): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];

        return in_array($file->getMimeType(), $documentTypes);
    }

    /**
     * Verifica se o arquivo é executável
     */
    private function isExecutableFile(UploadedFile $file): bool
    {
        $executableExtensions = [
            'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar',
            'php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp'
        ];

        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, $executableExtensions);
    }

    /**
     * Valida magic numbers do arquivo
     */
    private function validateMagicNumbers(UploadedFile $file): bool
    {
        $handle = fopen($file->getPathname(), 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 16);
        fclose($handle);

        $mimeType = $file->getMimeType();

        // Verificações específicas por tipo
        switch ($mimeType) {
            case 'image/jpeg':
                return str_starts_with($header, "\xFF\xD8\xFF");

            case 'image/png':
                return str_starts_with($header, "\x89PNG\r\n\x1A\n");

            case 'image/gif':
                return str_starts_with($header, "GIF87a") || str_starts_with($header, "GIF89a");

            case 'application/pdf':
                return str_starts_with($header, "%PDF");

            default:
                return true; // Para outros tipos, assumir válido
        }
    }

    /**
     * Verifica se o arquivo contém conteúdo suspeito
     */
    private function hasSuspiciousContent(UploadedFile $file): bool
    {
        // Para arquivos de texto, verificar conteúdo PHP
        if (str_starts_with($file->getMimeType(), 'text/')) {
            $content = file_get_contents($file->getPathname());
            if (str_contains($content, '<?php') || str_contains($content, '<?=')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o arquivo é um arquivo PHP
     */
    private function isPHPFile(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $phpExtensions = ['php', 'php3', 'php4', 'php5', 'phtml'];

        if (in_array($extension, $phpExtensions)) {
            return true;
        }

        // Verificar conteúdo para detectar PHP disfarçado
        $content = file_get_contents($file->getPathname());
        return str_contains($content, '<?php') || str_contains($content, '<?=');
    }

    /**
     * Converte string de tamanho para bytes
     */
    private function parseSize(string $size): int
    {
        $size = trim($size);
        $unit = strtoupper(substr($size, -2));
        $value = (int) substr($size, 0, -2);

        switch ($unit) {
            case 'KB':
                return $value * 1024;
            case 'MB':
                return $value * 1024 * 1024;
            case 'GB':
                return $value * 1024 * 1024 * 1024;
            default:
                return (int) $size;
        }
    }
}

