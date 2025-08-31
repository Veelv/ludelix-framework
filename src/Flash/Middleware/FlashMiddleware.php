<?php

namespace Ludelix\Flash\Middleware;

/**
 * FlashMiddleware - Handles flash messages between HTTP requests
 * 
 * This middleware handles the automatic clearing of flash messages
 * after they have been displayed to the user.
 * 
 * @package Ludelix\Flash\Middleware
 */
class FlashMiddleware
{
    /**
     * Handle the flash messages
     *
     * @param mixed $request
     * @param callable $next
     * @return mixed
     */
    public function handle($request, callable $next)
    {
        // Store the previous flash messages for use in this request
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (isset($_SESSION['flash_messages_old'])) {
                $_SESSION['flash_messages_previous'] = $_SESSION['flash_messages_old'];
                unset($_SESSION['flash_messages_old']);
            }
            
            // Move current flash messages to old
            if (isset($_SESSION['flash_messages'])) {
                $_SESSION['flash_messages_old'] = $_SESSION['flash_messages'];
            }
        }

        // Process the request
        $response = $next($request);

        return $response;
    }
}