<?php

namespace Ludelix\Core\Console\Commands\Connect\Installers;

class ConfigInstaller
{
    protected string $projectRoot;

    public function __construct()
    {
        $this->projectRoot = getcwd();
    }

    public function install(string $framework, array $options): void
    {
        $this->createViteConfig($framework, $options);
        
        if ($options['typescript']) {
            $this->createTsConfig();
        }
        
        if ($options['tailwind']) {
            $this->createTailwindConfig();
            $this->createPostCssConfig();
        }
    }

    protected function createViteConfig(string $framework, array $options): void
    {
        $config = $this->getViteConfig($framework, $options);
        $this->writeFile($this->projectRoot . '/vite.config.js', $config);
    }

    protected function createTsConfig(): void
    {
        $config = $this->getTsConfig();
        $this->writeFile($this->projectRoot . '/tsconfig.json', $config);
    }

    protected function createTailwindConfig(): void
    {
        $config = $this->getTailwindConfig();
        $this->writeFile($this->projectRoot . '/tailwind.config.js', $config);
    }

    protected function createPostCssConfig(): void
    {
        $config = $this->getPostCssConfig();
        $this->writeFile($this->projectRoot . '/postcss.config.js', $config);
    }

    protected function getViteConfig(string $framework, array $options): string
    {
        $ext = $options['typescript'] ? 'tsx' : 'jsx';
        
        return match($framework) {
            'react' => "import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  root: '.',
  build: {
    outDir: 'public/assets',
    assetsDir: '',
    rollupOptions: {
      input: 'frontend/js/app.js',
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: 'css/[name].[ext]'
      }
    }
  },
  resolve: {
    alias: {
      '@': '/frontend/js'
    }
  }
})",
            'vue' => "import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  root: '.',
  build: {
    outDir: 'public/assets',
    assetsDir: '',
    rollupOptions: {
      input: 'frontend/js/app.js',
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: 'css/[name].[ext]'
      }
    }
  },
  resolve: {
    alias: {
      '@': '/frontend/js'
    }
  }
})",
            'svelte' => "import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

export default defineConfig({
  plugins: [svelte()],
  root: '.',
  build: {
    outDir: 'public/assets',
    assetsDir: '',
    rollupOptions: {
      input: 'frontend/js/app.js',
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: 'css/[name].[ext]'
      }
    }
  },
  resolve: {
    alias: {
      '@': '/frontend/js'
    }
  }
})",
            default => throw new \Exception("Unsupported framework: {$framework}")
        };
    }

    protected function getTsConfig(): string
    {
        return '{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true
  },
  "include": ["frontend/js"]
}';
    }

    protected function getTailwindConfig(): string
    {
        return "/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './frontend/js/**/*.{js,jsx,ts,tsx,vue,svelte}',
    './templates/**/*.ludou'
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}";
    }

    protected function getPostCssConfig(): string
    {
        return "export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}";
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