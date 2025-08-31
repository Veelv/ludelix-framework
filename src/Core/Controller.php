<?php

namespace Ludelix\Core;

/**
 * Base Controller Class
 * 
 * This is the base class that all controllers should extend.
 * It provides common functionality that can be shared across all controllers.
 */
class Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize common controller functionality here
    }
    
    /**
     * Render a view with data
     * 
     * @param string $view The view file to render
     * @param array $data The data to pass to the view
     * @return mixed
     */
    protected function view(string $view, array $data = [])
    {
        return \Ludelix\Bridge\Bridge::ludou()->render($view, $data);
    }
}