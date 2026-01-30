<?php

namespace Ludelix\ApiExplorer\Http\Controller;

use Ludelix\ApiExplorer\Core\Scanner;
use Ludelix\PRT\Response;
use Ludelix\Routing\Core\Router;

/**
 * Controller for the Ludelix API Explorer.
 *
 * Orchestrates the generation and rendering of the API documentation
 * and provides the interactive interface for endpoint testing.
 *
 * @package Ludelix\ApiExplorer\Http\Controller
 */
class ExplorerController
{
    protected Scanner $scanner;

    /**
     * @param Router $router Injected router to initialize scanner.
     */
    public function __construct(Router $router)
    {
        $this->scanner = new Scanner($router);
    }

    /**
     * Renders the API Explorer UI.
     *
     * @return Response HTML response containing the explorer interface.
     */
    public function index(): Response
    {
        $html = $this->getHtmlTemplate();
        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Returns the generated API documentation as JSON.
     *
     * @return Response JSON response compatible with the explorer UI.
     */
    public function json(): Response
    {
        $data = $this->scanner->scan();
        return new Response(json_encode($data), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Returns the embedded HTML template for the UI.
     *
     * @return string
     */
    protected function getHtmlTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ludelix API Explorer</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --sidebar-bg: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent: #38bdf8;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --card-bg: #1e293b;
            --border: #334155;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            background: var(--bg-color);
            color: var(--text-primary);
            display: flex;
            height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            padding: 20px;
            overflow-y: auto;
        }
        .main {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }
        h1 { font-size: 1.5rem; margin-bottom: 2rem; color: var(--accent); }
        h2 { font-size: 1.25rem; margin-top: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; }
        
        .endpoint-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .endpoint-header {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            cursor: pointer;
            gap: 12px;
        }
        .endpoint-header:hover { background: #263345; }
        .method {
            font-weight: bold;
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 4px;
            min-width: 50px;
            text-align: center;
        }
        .method.GET { background: rgba(34, 197, 94, 0.2); color: var(--success); }
        .method.POST { background: rgba(56, 189, 248, 0.2); color: var(--accent); }
        .method.PUT { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
        .method.DELETE { background: rgba(239, 68, 68, 0.2); color: var(--danger); }
        
        .path { font-family: monospace; font-size: 0.9rem; flex: 1; }
        .summary { color: var(--text-secondary); font-size: 0.9rem; }
        
        .endpoint-details {
            display: none;
            padding: 16px;
            border-top: 1px solid var(--border);
            background: #172030;
        }
        .endpoint-details.active { display: block; }
        
        .param-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem; }
        .param-table th { text-align: left; padding: 8px; color: var(--text-secondary); border-bottom: 1px solid var(--border); }
        .param-table td { padding: 8px; border-bottom: 1px solid var(--border); }
        
        button.try-btn {
            background: var(--accent);
            color: #0f172a;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 16px;
        }
        button.try-btn:hover { opacity: 0.9; }
        
        .response-area {
            background: #0f172a;
            padding: 12px;
            border-radius: 4px;
            margin-top: 16px;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 0.85rem;
            display: none;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3>API Explorer</h3>
        <div id="nav-links">Loading...</div>
    </div>
    <div class="main">
        <div id="content">Loading documentation...</div>
    </div>

    <script>
        async function loadDocs() {
            try {
                const res = await fetch('/api-explorer/json');
                const data = await res.json();
                render(data);
            } catch (e) {
                document.getElementById('content').innerText = 'Failed to load API docs: ' + e.message;
            }
        }

        function render(data) {
            const nav = document.getElementById('nav-links');
            const content = document.getElementById('content');
            nav.innerHTML = '';
            content.innerHTML = '';

            Object.keys(data).forEach(tag => {
                const link = document.createElement('div');
                link.textContent = tag;
                link.style.padding = '8px 0';
                link.style.cursor = 'pointer';
                link.onclick = () => document.getElementById('group-' + tag).scrollIntoView();
                nav.appendChild(link);

                const group = document.createElement('div');
                group.id = 'group-' + tag;
                group.innerHTML = `<h2>${tag}</h2>`;
                
                data[tag].forEach(endpoint => {
                    group.appendChild(createEndpointCard(endpoint));
                });
                
                content.appendChild(group);
            });
        }

        function createEndpointCard(ep) {
            const card = document.createElement('div');
            card.className = 'endpoint-card';
            
            const header = document.createElement('div');
            header.className = 'endpoint-header';
            header.innerHTML = `
                <span class="method ${ep . method}">${ep . method}</span>
                <span class="path">${ep . path}</span>
                <span class="summary">${ep . summary}</span>
            `;
            header.onclick = () => {
                const details = card.querySelector('.endpoint-details');
                details.style.display = details.style.display === 'block' ? 'none' : 'block';
            };
            
            const details = document.createElement('div');
            details.className = 'endpoint-details';
            details.innerHTML = `
                <p>${ep . description}</p>
                ${renderParams(ep . queryParams, 'Query Parameters')}
                ${renderParams(ep . bodyParams, 'Body Parameters')}
                <button class="try-btn" onclick="executeRequest(this, '${ep . method}', '${ep . path}')">Try it out</button>
                <div class="response-area"></div>
            `;
            
            card.appendChild(header);
            card.appendChild(details);
            return card;
        }

        function renderParams(params, title) {
            if (!params || params.length === 0) return '';
            const rows = params.map(p => `
                <tr>
                    <td><code>${p.name}</code>${p.required ? ' <span style="color:var(--danger)">*</span>' : ''}</td>
                    <td>${p.type}</td>
                    <td>${p.description}</td>
                    <td><code style="color:var(--warning)">${p.rules || ''}</code></td>
                    <td><input type="text" class="input-param" data-name="${p.name}" placeholder="Value"></td>
                </tr>
            `).join('');
            
            return `
                <h4>${title}</h4>
                <table class="param-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th><th>Rules</th><th>Value</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }

        async function executeRequest(btn, method, path) {
            const container = btn.parentElement;
            const output = container.querySelector('.response-area');
            output.innerHTML = 'Sending...';
            output.style.display = 'block';
            
            // Collect inputs
            const inputs = container.querySelectorAll('input.input-param');
            const queryData = {};
            const bodyData = {};
            
            // This is a simplified logic. Ideally we differentiate query and body inputs by context.
            // For now, assume if method is GET -> everything in query. If POST -> body.
            inputs.forEach(input => {
                if (input.value) {
                    if (method === 'GET') queryData[input.dataset.name] = input.value;
                    else bodyData[input.dataset.name] = input.value;
                }
            });

            let url = path;
            if (Object.keys(queryData).length > 0) {
                url += '?' + new URLSearchParams(queryData).toString();
            }

            try {
                const options = {
                    method: method,
                    headers: { 'Content-Type': 'application/json' }
                };
                if (method !== 'GET') {
                    options.body = JSON.stringify(bodyData);
                }
                
                const res = await fetch(url, options);
                const data = await res.json();
                output.textContent = JSON.stringify(data, null, 2);
                output.style.borderLeft = res.ok ? '3px solid var(--success)' : '3px solid var(--danger)';
            } catch (e) {
                output.textContent = 'Error: ' + e.message;
            }
        }

        loadDocs();
    </script>
</body>
</html>
HTML;
    }
}
