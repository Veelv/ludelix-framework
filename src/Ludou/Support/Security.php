<?php

namespace Ludelix\Ludou\Support;

/**
 * Template Security
 * 
 * Provides security utilities for template rendering
 */
class Security
{
    protected static array $allowedFunctions = [
        't', 'connect', 'asset', 'service', 'config', 'route', 'csrf_token', 'date'
    ];

    protected static array $blockedFunctions = [
        'exec', 'system', 'shell_exec', 'passthru', 'eval', 'file_get_contents', 
        'file_put_contents', 'fopen', 'fwrite', 'include', 'require'
    ];

    public static function escape(mixed $value): string
    {
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        if (is_array($value) || is_object($value)) {
            return htmlspecialchars(json_encode($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function sanitizeExpression(string $expression): string
    {
        // Remove potentially dangerous function calls
        foreach (self::$blockedFunctions as $func) {
            if (str_contains($expression, $func)) {
                throw new \Exception("Blocked function '$func' in template expression");
            }
        }

        return $expression;
    }

    public static function validateFunction(string $function): bool
    {
        return in_array($function, self::$allowedFunctions) && 
               !in_array($function, self::$blockedFunctions);
    }

    public static function addAllowedFunction(string $function): void
    {
        if (!in_array($function, self::$allowedFunctions)) {
            self::$allowedFunctions[] = $function;
        }
    }

    public static function removeAllowedFunction(string $function): void
    {
        self::$allowedFunctions = array_filter(
            self::$allowedFunctions, 
            fn($f) => $f !== $function
        );
    }

    public static function csrfToken(): string
    {
        // Will integrate with CSRF system later
        return bin2hex(random_bytes(32));
    }

    public static function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }
}
