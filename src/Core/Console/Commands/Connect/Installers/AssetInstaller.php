<?php

namespace Ludelix\Core\Console\Commands\Connect\Installers;

class AssetInstaller
{
    protected string $projectRoot;

    public function __construct()
    {
        $this->projectRoot = getcwd();
    }

    public function install(string $framework, array $options): void
    {
        $this->createFrontendStructure();
        $this->createCssFile($options);
        $this->createJsFile($framework, $options);
    }

    protected function createFrontendStructure(): void
    {
        $directories = [
            $this->projectRoot . '/frontend/js/components',
            $this->projectRoot . '/frontend/js/pages',
            $this->projectRoot . '/frontend/css'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    protected function createCssFile(array $options): void
    {
        $cssContent = $this->getCssContent($options);
        $this->writeFile($this->projectRoot . '/frontend/css/app.css', $cssContent);
    }

    protected function createJsFile(string $framework, array $options): void
    {
        $jsContent = $this->getJsContent($framework, $options);
        $this->writeFile($this->projectRoot . '/frontend/js/app.js', $jsContent);
    }

    protected function getCssContent(array $options): string
    {
        if ($options['tailwind']) {
            return "@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom styles */
body {
    font-family: 'Inter', sans-serif;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}";
        }

        return "/* Custom styles */
body {
    font-family: 'Inter', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f8f9fa;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.navbar {
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1rem 0;
}

.navbar h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.main-content {
    padding: 2rem 0;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #1f2937;
}

.page-content {
    color: #6b7280;
    line-height: 1.6;
}";
    }

    protected function getJsContent(string $framework, array $options): string
    {
        $ext = $options['typescript'] ? 'tsx' : 'jsx';
        
        return match($framework) {
            'react' => "import React from 'react'
import { createRoot } from 'react-dom/client'
import App from './components/App.{$ext}'
import './ludelix-connect.js'
import '../css/app.css'

const container = document.getElementById('app')
if (container) {
    const root = createRoot(container)
    root.render(<App />)
}",
            'vue' => "import { createApp } from 'vue'
import App from './components/App.vue'
import './ludelix-connect.js'
import '../css/app.css'

const app = createApp(App)
app.mount('#app')",
            'svelte' => "import App from './components/App.svelte'
import './ludelix-connect.js'
import '../css/app.css'

const app = new App({
    target: document.getElementById('app')
})

export default app",
            default => throw new \Exception("Unsupported framework: {$framework}")
        };
    }

    protected function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $content);
    }
} 