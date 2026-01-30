<?php

namespace Ludelix\ApiExplorer\SDK;

/**
 * SdkGenerator - Generates TypeScript SDK with ludelix-connect integration
 * 
 * Generates React hooks that use ludelix-connect for SPA navigation,
 * ensuring proper integration with Connect backend.
 */
class SdkGenerator
{
    protected string $framework = 'react'; // react, vue, svelte
    
    /**
     * Generate the TS code from the schema.
     */
    public function generate(array $schema): string
    {
        $code = "/**\n * Ludelix Generated SDK\n * @generated\n * \n * This SDK uses ludelix-connect for SPA navigation.\n * All requests automatically include:\n * - X-Ludelix-Connect header\n * - CSRF token\n * - Asset versioning\n * - History management\n */\n\n";
        
        // Import ludelix-connect
        $code .= $this->generateImports();
        $code .= "\n";
        
        // Generate types
        $code .= $this->generateTypes($schema);
        $code .= "\n";
        
        // Group endpoints by resource
        $grouped = $this->groupByResource($schema);
        
        // Generate hooks for each resource
        foreach ($grouped as $resource => $endpoints) {
            $code .= $this->generateHook($resource, $endpoints);
            $code .= "\n";
        }
        
        return $code;
    }
    
    /**
     * Generate imports
     */
    protected function generateImports(): string
    {
        return match($this->framework) {
            'react' => "import { useNavigation } from 'ludelix-connect/react';",
            'vue' => "import { useNavigation } from 'ludelix-connect/vue';",
            'svelte' => "import { getNavigation } from 'ludelix-connect/svelte';",
            default => "import { useNavigation } from 'ludelix-connect/react';"
        };
    }
    
    /**
     * Generate TypeScript types
     */
    protected function generateTypes(array $schema): string
    {
        $code = "// Types\n";
        
        foreach ($schema as $endpoint) {
            // Generate request type if has body params
            if (!empty($endpoint['bodyParams'])) {
                $typeName = $this->getRequestTypeName($endpoint['name']);
                $code .= "export interface {$typeName} {\n";
                
                foreach ($endpoint['bodyParams'] as $param) {
                    $optional = $param['required'] ? '' : '?';
                    $code .= "    {$param['name']}{$optional}: {$param['type']};\n";
                }
                
                $code .= "}\n\n";
            }
            
            // Generate query params type if needed
            if (!empty($endpoint['queryParams'])) {
                $typeName = $this->getQueryTypeName($endpoint['name']);
                $code .= "export interface {$typeName} {\n";
                
                foreach ($endpoint['queryParams'] as $param) {
                    $optional = $param['required'] ? '' : '?';
                    $code .= "    {$param['name']}{$optional}: {$param['type']};\n";
                }
                
                $code .= "}\n\n";
            }
        }
        
        return $code;
    }
    
    /**
     * Group endpoints by resource
     */
    protected function groupByResource(array $schema): array
    {
        $grouped = [];
        
        foreach ($schema as $endpoint) {
            $resource = $this->extractResource($endpoint['path']);
            $grouped[$resource][] = $endpoint;
        }
        
        return $grouped;
    }
    
    /**
     * Extract resource name from path
     */
    protected function extractResource(string $path): string
    {
        // /api/user/123 -> user
        // /user -> user
        $parts = explode('/', trim($path, '/'));
        
        // Skip 'api' prefix if exists
        if ($parts[0] === 'api' && isset($parts[1])) {
            return $parts[1];
        }
        
        return $parts[0] ?? 'default';
    }
    
    /**
     * Generate a React hook for a resource
     */
    protected function generateHook(string $resource, array $endpoints): string
    {
        $hookName = 'use' . ucfirst($resource) . 'Api';
        
        $code = "/**\n * {$hookName} - Generated API hook for {$resource}\n */\n";
        $code .= "export const {$hookName} = () => {\n";
        $code .= "    const nav = useNavigation();\n\n";
        $code .= "    return {\n";
        
        foreach ($endpoints as $index => $endpoint) {
            $code .= $this->generateMethod($endpoint);
            
            // Add comma if not last
            if ($index < count($endpoints) - 1) {
                $code .= ",\n";
            }
        }
        
        $code .= "\n    };\n";
        $code .= "};\n";
        
        return $code;
    }
    
    /**
     * Generate a method for an endpoint
     */
    protected function generateMethod(array $endpoint): string
    {
        $methodName = $this->getMethodName($endpoint['name'], $endpoint['method']);
        $httpMethod = strtolower($endpoint['method']);
        
        // Build parameters
        $params = [];
        $pathParams = $this->extractPathParams($endpoint['path']);
        
        // Add path parameters
        foreach ($pathParams as $param) {
            $params[] = "{$param}: number | string";
        }
        
        // Add query parameters
        if (!empty($endpoint['queryParams'])) {
            $queryType = $this->getQueryTypeName($endpoint['name']);
            $params[] = "query: {$queryType}";
        }
        
        // Add body parameters
        if (!empty($endpoint['bodyParams'])) {
            $bodyType = $this->getRequestTypeName($endpoint['name']);
            $params[] = "data: {$bodyType}";
        }
        
        $paramsStr = implode(', ', $params);
        
        // Build method documentation
        $code = "        /**\n";
        $code .= "         * {$endpoint['method']} {$endpoint['path']}\n";
        if ($endpoint['auth']) {
            $code .= "         * @auth required\n";
        }
        $code .= "         */\n";
        
        // Build method signature
        $code .= "        {$methodName}: ({$paramsStr}) => {\n";
        
        // Build URL with path parameters
        $url = $this->buildUrlWithParams($endpoint['path'], $pathParams);
        $code .= "            const url = `{$url}`;\n";
        
        // Build navigation call
        if ($httpMethod === 'get') {
            if (!empty($endpoint['queryParams'])) {
                $code .= "            return nav.get(url, query);\n";
            } else {
                $code .= "            return nav.visit(url);\n";
            }
        } else {
            $dataArg = !empty($endpoint['bodyParams']) ? 'data' : '{}';
            $code .= "            return nav.{$httpMethod}(url, {$dataArg});\n";
        }
        
        $code .= "        }";
        
        return $code;
    }
    
    /**
     * Extract path parameters from path
     */
    protected function extractPathParams(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        return $matches[1] ?? [];
    }
    
    /**
     * Build URL with template literals for path params
     */
    protected function buildUrlWithParams(string $path, array $params): string
    {
        $url = $path;
        foreach ($params as $param) {
            $url = str_replace("{{$param}}", "\${$param}", $url);
        }
        return $url;
    }
    
    /**
     * Get method name from endpoint name and HTTP method
     */
    protected function getMethodName(string $name, string $httpMethod): string
    {
        // user_update -> update
        // user_show -> show
        // user_index -> list
        
        $parts = explode('_', $name);
        $action = end($parts);
        
        // Map common actions
        $actionMap = [
            'index' => 'list',
            'show' => 'get',
            'store' => 'create',
            'destroy' => 'delete',
        ];
        
        return $actionMap[$action] ?? $action;
    }
    
    /**
     * Get request type name
     */
    protected function getRequestTypeName(string $endpointName): string
    {
        $name = str_replace('_', '', ucwords($endpointName, '_'));
        return $name . 'Data';
    }
    
    /**
     * Get query type name
     */
    protected function getQueryTypeName(string $endpointName): string
    {
        $name = str_replace('_', '', ucwords($endpointName, '_'));
        return $name . 'Query';
    }
    
    /**
     * Set framework adapter
     */
    public function setFramework(string $framework): self
    {
        $this->framework = $framework;
        return $this;
    }
}
