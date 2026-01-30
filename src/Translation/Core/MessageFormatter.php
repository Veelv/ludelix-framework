<?php

namespace Ludelix\Translation\Core;

/**
 * Message Formatter
 * 
 * Handles parameter replacement and pluralization
 */
class MessageFormatter
{
    /**
     * Format message with parameters
     */
    public function format(string $message, array $parameters = []): string
    {
        if (empty($parameters)) {
            return $message;
        }

        foreach ($parameters as $key => $value) {
            $message = str_replace([':' . $key, '{' . $key . '}'], $value, $message);
        }

        return $message;
    }

    /**
     * Handle pluralization
     */
    public function pluralize(string $message, int $count): string
    {
        $parts = explode('|', $message);
        
        if (count($parts) === 1) {
            return $message;
        }

        // Simple pluralization: 0 = first, 1 = second, >1 = third (or second if no third)
        if ($count === 0) {
            return $parts[0];
        } elseif ($count === 1) {
            return $parts[1] ?? $parts[0];
        } else {
            return $parts[2] ?? $parts[1] ?? $parts[0];
        }
    }

    /**
     * Check if message has pluralization
     */
    public function hasPluralization(string $message): bool
    {
        return str_contains($message, '|');
    }
}