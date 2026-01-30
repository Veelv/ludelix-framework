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
        // Store the previous flash messages for use in this request
        $session = \Ludelix\Bridge\Bridge::session();

        if ($session->isStarted()) {
            if ($session->has('flash_messages_old')) {
                $session->put('flash_messages_previous', $session->get('flash_messages_old'));
                $session->remove('flash_messages_old');
            }

            // Move current flash messages to old
            if ($session->has('flash_messages')) {
                $session->put('flash_messages_old', $session->get('flash_messages'));
            }
        }

        // Process the request
        $response = $next($request);

        return $response;
    }
}