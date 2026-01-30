<?php

namespace Ludelix\Security\Exceptions;

use Exception;

/**
 * TokenMismatchException
 * 
 * Thrown when CSRF token validation fails
 */
class TokenMismatchException extends Exception
{
    /**
     * HTTP status code for this exception
     */
    protected $code = 419;

    /**
     * Create a new token mismatch exception
     *
     * @param string $message
     */
    public function __construct(string $message = 'CSRF token mismatch.')
    {
        parent::__construct($message, 419);
    }

    /**
     * Get the HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return 419;
    }

    /**
     * Render the exception as an HTTP response
     *
     * @return array
     */
    public function render(): array
    {
        return [
            'error' => 'CSRF token mismatch',
            'message' => $this->getMessage(),
            'status' => 419
        ];
    }
}
