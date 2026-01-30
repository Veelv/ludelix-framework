<?php

namespace Ludelix\Flash\Exceptions;

use Exception;

/**
 * FlashException - Base exception for flash messaging system
 * 
 * This is the base exception class for all flash messaging related exceptions.
 * 
 * @package Ludelix\Flash\Exceptions
 */
class FlashException extends Exception
{
    /**
     * FlashException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}