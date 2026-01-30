<?php

namespace Ludelix\Connect\Helpers;

/**
 * ConnectHelper - Helper for Connect template directives
 * 
 * Provides #[connect:head] and #[connect:root] directives
 * for professional SPA integration.
 */
class ConnectHelper
{
    /**
     * Render Connect head section
     * 
     * Usage: #[connect:head]
     * 
     * Generates:
     * - window.__LUDELIX_CONNECT__ with page data
     * - Meta tags for Connect
     */
    public function renderHead(array $page): string
    {
        $pageJson = json_encode($page, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return <<<HTML
<script>
window.__LUDELIX_CONNECT__ = {$pageJson};
</script>

HTML;
    }

    /**
     * Render Connect root element
     * 
     * Usage: #[connect:root]
     * 
     * Generates:
     * - <div id="app" data-page="...">
     */
    public function renderRoot(array $page, array $options = []): string
    {
        $id = $options['id'] ?? 'app';
        $pageJson = htmlspecialchars(
            json_encode($page, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
            ENT_QUOTES,
            'UTF-8'
        );

        return "<div id=\"{$id}\" data-page='{$pageJson}'></div>";
    }

    /**
     * Render Connect meta tags
     * 
     * Usage: #[connect:meta]
     * 
     * Generates:
     * - CSRF token
     * - Connect version
     */
    public function renderMeta(array $page): string
    {
        $html = '';

        // CSRF token
        if (function_exists('csrf_token')) {
            $token = csrf_token();
            $html .= "<meta name=\"csrf-token\" content=\"{$token}\">\n";
        }

        // Connect version
        if (isset($page['version'])) {
            $html .= "<meta name=\"ludelix-version\" content=\"{$page['version']}\">\n";
        }

        return $html;
    }

    /**
     * Render Connect title tag
     * 
     * Usage: <title #[connect:title]>Default Title</title>
     * 
     * Generates dynamic title that updates with page navigation
     */
    public static function renderTitle(array $page): string
    {
        // If page has a title prop, use it
        if (isset($page['props']['title'])) {
            return htmlspecialchars($page['props']['title'], ENT_QUOTES, 'UTF-8');
        }

        // Return empty string to use default title from template
        return '';
    }
}
