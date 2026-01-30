<?php

namespace Ludelix\Flash\Exceptions;

/**
 * InvalidMessageException - Exception for invalid message types
 * 
 * This exception is thrown when an invalid message type is encountered.
 * 
 * @package Ludelix\Flash\Exceptions
 */
class InvalidMessageException extends FlashException
{
    /**
     * InvalidMessageException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(string $message = "Invalid message type provided", int $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}