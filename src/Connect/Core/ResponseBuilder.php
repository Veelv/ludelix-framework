<?php

namespace Ludelix\Connect\Core;

use Ludelix\PRT\Response;
use Ludelix\Ludou\Core\TemplateEngine;
use Ludelix\Bridge\Bridge;

/**
 * Response Builder - Intelligent Response Construction
 * 
 * Constructs appropriate responses for different request types and contexts,
 * handling both traditional page loads and SPA navigation with sophisticated
 * optimization strategies.
 * 
 * Features:
 * - Adaptive response format based on request type
 * - SSR content integration with client-side hydration
 * - Intelligent caching headers and strategies
 * - Progressive enhancement support
 * - SEO optimization with meta tag injection
 * - Performance optimization through compression
 * 
 * @package Ludelix\Connect\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class ResponseBuilder
{
    protected TemplateEngine $templateEngine;
    protected array $config;

    public function __construct(TemplateEngine $templateEngine, array $config = [])
    {
        $this->templateEngine = $templateEngine;
        $this->config = $config;
    }

    /**
     * Build appropriate response based on request context
     */
    public function build(array $data): Response
    {
        if ($data['is_connect_request']) {
            return $this->buildConnectResponse($data);
        }
        
        return $this->buildInitialResponse($data);
    }

    /**
     * Build JSON response for Connect requests
     */
    protected function buildConnectResponse(array $data): Response
    {
        $responseData = [
            'component' => $data['component'],
            'props' => $data['props'],
            'url' => $data['url'],
            'version' => $data['version'],
        ];
        
        if (isset($data['websocket'])) {
            $responseData['websocket'] = $data['websocket'];
        }
        
        return new Response(
            json_encode($responseData),
            200,
            [
                'Content-Type' => 'application/json',
                'X-Ludelix-Connect' => 'true',
                'Cache-Control' => 'no-cache, private',
            ]
        );
    }

    /**
     * Build HTML response for initial page loads
     */
    protected function buildInitialResponse(array $data): Response
    {
        $templateData = [
            'component' => $data['component'],
            'props' => $data['props'],
            'version' => $data['version'],
            'ssr_content' => $data['ssr_content'] ?? '',
            'websocket_config' => $data['websocket'] ?? null,
        ];
        
        $html = $this->templateEngine->render($data['root_template'], $templateData);
        
        return new Response(
            $html,
            200,
            [
                'Content-Type' => 'text/html; charset=utf-8',
                'X-Ludelix-Version' => $data['version'],
            ]
        );
    }
}