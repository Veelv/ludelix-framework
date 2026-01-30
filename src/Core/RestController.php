<?php

namespace Ludelix\Core;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * Base REST Controller Class
 * 
 * This is the base class that all REST controllers should extend.
 * It provides common functionality for RESTful API endpoints.
 */
class RestController extends Controller
{
    /**
     * Send a JSON response
     * 
     * @param mixed $data The data to send as JSON
     * @param int $statusCode The HTTP status code (default: 200)
     * @param array $headers Additional headers to send
     * @return mixed
     */
    protected function json(mixed $data, int $statusCode = 200, array $headers = [])
    {
        // Set default content type if not provided
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }
        
        // Create response
        $response = new Response(json_encode($data), $statusCode, $headers);
        
        return $response;
    }
    
    /**
     * Send a success response
     * 
     * @param mixed $data The data to send
     * @param string $message Optional success message
     * @param int $statusCode The HTTP status code (default: 200)
     * @return mixed
     */
    protected function success(mixed $data = null, string $message = '', int $statusCode = 200)
    {
        $response = [
            'success' => true,
            'data' => $data
        ];
        
        if (!empty($message)) {
            $response['message'] = $message;
        }
        
        return $this->json($response, $statusCode);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message The error message
     * @param int $statusCode The HTTP status code (default: 400)
     * @param mixed $errors Additional error details
     * @return mixed
     */
    protected function error(string $message, int $statusCode = 400, mixed $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $statusCode);
    }
    
    /**
     * Send a not found response
     * 
     * @param string $message The error message
     * @return mixed
     */
    protected function notFound(string $message = 'Resource not found')
    {
        return $this->error($message, 404);
    }
    
    /**
     * Send a validation error response
     * 
     * @param mixed $errors The validation errors
     * @param string $message The error message
     * @return mixed
     */
    protected function validationError(mixed $errors, string $message = 'Validation failed')
    {
        return $this->error($message, 422, $errors);
    }
}