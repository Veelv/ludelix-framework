<?php

namespace Ludelix\Core\Console\Commands\Connect\Generators;

class ReactGenerator extends BaseGenerator
{
    public function generate(array $options): void
    {
        $this->options = $options;

        // Create frontend structure
        $this->createFrontendStructure();
        
        // Create configuration files
        $this->createConfigurationFiles();
        
        // Create React components
        $this->createReactComponents();
        
        // Create CSS file
        $this->createCssFile();
        
        // Create templates
        $this->createTemplates();
        
        // Create routes
        $this->createRoutes();
    }

    protected function createFrontendStructure(): void
    {
        // Create directories
        $this->createDirectory($this->projectRoot . '/frontend/js/components');
        $this->createDirectory($this->projectRoot . '/frontend/js/pages');
        $this->createDirectory($this->projectRoot . '/frontend/css');
    }

    protected function createConfigurationFiles(): void
    {
        // Update package.json with React dependencies
        $dependencies = [
            'react' => '^18.2.0',
            'react-dom' => '^18.2.0',
            'ludelix-connect' => '^1.0.0'
        ];

        $devDependencies = [
            '@vitejs/plugin-react' => '^4.0.0',
            'vite' => '^4.4.0'
        ];

        if ($this->options['typescript']) {
            $devDependencies['@types/react'] = '^18.2.0';
            $devDependencies['@types/react-dom'] = '^18.2.0';
            $devDependencies['typescript'] = '^5.0.0';
        }

        if ($this->options['tailwind']) {
            $devDependencies['tailwindcss'] = '^3.3.0';
            $devDependencies['autoprefixer'] = '^10.4.0';
            $devDependencies['postcss'] = '^8.4.0';
        }

        $this->updatePackageJson($dependencies, $devDependencies);

        // Create Vite config
        $this->createViteConfig('react', $this->options);

        // Create TypeScript config
        if ($this->options['typescript']) {
            $this->createTsConfig();
            $this->createTsConfigNode();
        }

        // Create Tailwind config
        if ($this->options['tailwind']) {
            $this->createTailwindConfig();
            $this->createPostCssConfig();
        }
    }

    protected function createReactComponents(): void
    {
        // Create app.js entry point
        $entryPoint = $this->getEntryPoint();
        $this->writeFile($this->projectRoot . '/frontend/js/app.js', $entryPoint);

        // Create App component
        $appComponent = $this->getAppComponent();
        $this->writeFile($this->projectRoot . '/frontend/js/components/App.jsx', $appComponent);

        // Create Layout component
        $layoutComponent = $this->getLayoutComponent();
        $this->writeFile($this->projectRoot . '/frontend/js/components/Layout.jsx', $layoutComponent);

        // Create pages
        $this->createPages();

        // Create ludelix-connect config
        $connectConfig = $this->getConnectConfig();
        $this->writeFile($this->projectRoot . '/frontend/js/ludelix-connect.js', $connectConfig);
    }

    protected function createPages(): void
    {
        $pages = [
            'Home.jsx' => $this->getHomePage(),
            'About.jsx' => $this->getAboutPage(),
            'Contact.jsx' => $this->getContactPage()
        ];

        foreach ($pages as $filename => $content) {
            $this->writeFile($this->projectRoot . '/frontend/js/pages/' . $filename, $content);
        }
    }

    protected function createTemplates(): void
    {
        // Create main app template
        $appTemplate = $this->getAppTemplate();
        $this->writeFile($this->projectRoot . '/frontend/templates/app.ludou', $appTemplate);

        // Create connect config
        $this->createConnectConfig();
    }

    protected function createConnectConfig(): void
    {
        $config = $this->getConnectConfigFile();
        $this->writeFile($this->projectRoot . '/config/connect.php', $config);
    }

    protected function createRoutes(): void
    {
        // Update web.php
        $webRoutes = $this->getWebRoutes();
        $this->writeFile($this->projectRoot . '/routes/web.php', $webRoutes);

        // Create api.php
        $apiRoutes = $this->getApiRoutes();
        $this->writeFile($this->projectRoot . '/routes/api.php', $apiRoutes);
    }

    // Configuration methods
    protected function getViteConfig(string $framework, array $options): string
    {
        $ext = $options['typescript'] ? 'tsx' : 'jsx';
        
        return "import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'

export default defineConfig({
  plugins: [react()],
  root: 'frontend',
  build: {
    outDir: '../public/assets',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'frontend/js/app.js'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name].css';
          }
          return 'assets/[name].[ext]';
        },
      },
    },
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  resolve: {
    alias: {
      '@': '/frontend/js'
    }
  }
})";
    }

    protected function getTailwindConfig(): string
    {
        return "/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './frontend/**/*.{js,ts,jsx,tsx}',
    './frontend/templates/**/*.ludou',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      colors: {
        primary: {
          50: '#eff6ff',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
        },
      },
    },
  },
  plugins: [],
}";
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
    "jsx": "react-jsx",
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true
  },
  "include": ["frontend/**/*"],
  "references": [{ "path": "./tsconfig.node.json" }]
}';
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

    // Component methods
    protected function getEntryPoint(): string
    {
        return "import { init } from 'ludelix-connect';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { setupReact } from 'ludelix-connect/react';

// Initialize Connect
init({
  el: '#app',
  resolve: (name) => {
    const pages = import.meta.glob('./pages/*.jsx', { eager: true });
    return pages[\`./pages/\${name}.jsx\`];
  },
  setup: ({ el, App, props }) => {
    const root = createRoot(el);
    root.render(React.createElement(App, props));
  }
});

// Setup React adapter
setupReact({
  el: '#app',
  resolve: (name) => {
    const pages = import.meta.glob('./pages/*.jsx', { eager: true });
    return pages[\`./pages/\${name}.jsx\`];
  }
});";
    }

    protected function getAppComponent(): string
    {
        return "import React from 'react';
import { usePage, useNavigation } from 'ludelix-connect/react';

export default function App() {
  const page = usePage();
  const navigation = useNavigation();

  return (
    <div className='container mx-auto px-4 py-8'>
      <h1 className='text-3xl font-bold mb-4'>Welcome to Ludelix</h1>
      <p className='text-gray-600'>Your modern PHP + React application</p>
      
      <div className='mt-6'>
        <h2 className='text-xl font-semibold mb-4'>Page Information</h2>
        <div className='space-y-2'>
          <p><strong>Component:</strong> {page.component}</p>
          <p><strong>URL:</strong> {page.url}</p>
          <p><strong>Version:</strong> {page.version}</p>
        </div>
        
        <div className='mt-6'>
          <h3 className='text-lg font-medium mb-3'>Navigation Test</h3>
          <div className='space-x-4'>
            <button 
              onClick={() => navigation.visit('/')}
              className='bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded'
            >
              Home
            </button>
            <button 
              onClick={() => navigation.visit('/about')}
              className='bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded'
            >
              About
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}";
    }

    protected function getLayoutComponent(): string
    {
        return "import React from 'react'

function Layout({ children }) {
  return (
    <div className='min-h-screen bg-gray-50'>
      <nav className='bg-white shadow-sm'>
        <div className='container mx-auto px-4 py-4'>
          <h1 className='text-xl font-semibold'>Ludelix App</h1>
        </div>
      </nav>
      <main className='py-8'>
        {children}
      </main>
    </div>
  )
}

export default Layout";
    }

    protected function getHomePage(): string
    {
        return "import React from 'react';
import { usePage, useNavigation } from 'ludelix-connect/react';

export default function Home() {
  const page = usePage();
  const navigation = useNavigation();

  return (
    <div className='container mx-auto px-4 py-8'>
      <h1 className='text-3xl font-bold mb-6'>Home</h1>
      <p className='text-gray-600 mb-6'>Welcome to your Ludelix application!</p>
      
      <div className='bg-white rounded-lg shadow-md p-6'>
        <h2 className='text-xl font-semibold mb-4'>Page Information</h2>
        <div className='space-y-2'>
          <p><strong>Component:</strong> {page.component}</p>
          <p><strong>URL:</strong> {page.url}</p>
          <p><strong>Version:</strong> {page.version}</p>
        </div>
        
        <div className='mt-6'>
          <h3 className='text-lg font-medium mb-3'>Navigation</h3>
          <div className='space-x-4'>
            <button 
              onClick={() => navigation.visit('/about')}
              className='bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded'
            >
              About
            </button>
            <button 
              onClick={() => navigation.visit('/contact')}
              className='bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded'
            >
              Contact
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}";
    }

    protected function getAboutPage(): string
    {
        return "import React from 'react'

function About() {
  return (
    <div>
      <h1 className='text-2xl font-bold mb-4'>About</h1>
      <p>This is the about page.</p>
    </div>
  )
}

export default About";
    }

    protected function getContactPage(): string
    {
        return "import React from 'react'

function Contact() {
  return (
    <div>
      <h1 className='text-2xl font-bold mb-4'>Contact</h1>
      <p>Get in touch with us.</p>
    </div>
  )
}

export default Contact";
    }

    protected function getConnectConfig(): string
    {
        return "import { init } from 'ludelix-connect'

init({
  el: '#app',
  resolve: (name) => {
    const pages = import.meta.glob('./pages/*.jsx', { eager: true })
    return pages[`./pages/\${name}.jsx`]
  }
})";
    }

    // Template methods
    protected function getAppTemplate(): string
    {
        return '<!DOCTYPE html>
<html lang="#[config(\'app.locale\')]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>#[config(\'app.name\')]</title>
    <meta name="csrf-token" content="#[csrf_token()]">
    #[connect]
        <script type="module" src="/assets/js/app.js"></script>
        <link rel="stylesheet" href="/assets/css/app.css">
    #[else]
        <script type="module" src="/assets/js/app.js"></script>
        <link rel="stylesheet" href="/assets/css/app.css">
    #[endconnect]
</head>
<body>
    #[connect]
        <div id="app" data-page=\'#[json_encode([
            "component" => component,
            "props" => props,
            "url" => request()->getUri(),
            "version" => version
        ])]\'></div>
    #[else]
        <div id="app">
            #yield content
        </div>
    #[endconnect]
</body>
</html>';
    }

    protected function getConnectConfigFile(): string
    {
        return '<?php

return [
    \'enabled\' => true,
    \'framework\' => \'react\',
    \'entry_point\' => \'frontend/js/app.js\',
    \'template\' => \'frontend/templates/app.ludou\',
    \'build_path\' => \'public/build\',
    \'dev_server\' => [
        \'host\' => \'localhost\',
        \'port\' => 5173
    ],
    \'vite\' => [
        \'manifest\' => \'public/build/manifest.json\',
        \'dev_server\' => \'http://localhost:5173\'
    ]
];';
    }

    // Route methods
    protected function getWebRoutes(): string
    {
        return '<?php

use Ludelix\Bridge\Bridge;
use Ludelix\Http\Request;
use Ludelix\Connect\Connect;

// Home route
Bridge::route()->get(\'/\', function (Request $request) {
    return Connect::render(\'Home\', [
        \'title\' => \'Welcome to Ludelix\',
        \'message\' => \'Your modern PHP + React application\'
    ]);
});

// About route
Bridge::route()->get(\'/about\', function (Request $request) {
    return Connect::render(\'About\', [
        \'title\' => \'About Us\',
        \'message\' => \'Learn more about our application\'
    ]);
});

// Contact route
Bridge::route()->get(\'/contact\', function (Request $request) {
    return Connect::render(\'Contact\', [
        \'title\' => \'Contact Us\',
        \'message\' => \'Get in touch with us\'
    ]);
});

// Catch-all route for SPA navigation
Bridge::route()->get(\'/{\'path\': \'.*\'}\', function (Request $request) {
    return Connect::render(\'Home\', [
        \'title\' => \'Welcome to Ludelix\',
        \'message\' => \'Your modern PHP + React application\'
    ]);
});';
    }

    protected function getApiRoutes(): string
    {
        return '<?php

use Ludelix\Bridge\Bridge;
use Ludelix\Http\Request;

// API routes para o frontend
Bridge::route()->prefix(\'/api\')->group(function() {
    Bridge::route()->get(\'/user\', function (Request $request) {
        return response()->json([
            \'user\' => Bridge::auth()->user()
        ]);
    });
});';
    }

    protected function createTsConfigNode(): void
    {
        $config = $this->getTsConfigNode();
        $this->writeFile($this->projectRoot . '/tsconfig.node.json', $config);
    }

    protected function getTsConfigNode(): string
    {
        return '{
  "compilerOptions": {
    "composite": true,
    "skipLibCheck": true,
    "module": "ESNext",
    "moduleResolution": "bundler",
    "allowSyntheticDefaultImports": true
  },
  "include": ["vite.config.js"]
}';
    }

    protected function getCssContent(): string
    {
        return "@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom styles */
.ludelix-loading {
  @apply flex items-center justify-center p-8 text-gray-600;
}

/* Container utilities */
.container {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8;
}

/* Button utilities */
.btn {
  @apply px-4 py-2 rounded font-medium transition-colors duration-200;
}

.btn-primary {
  @apply bg-blue-500 hover:bg-blue-600 text-white;
}

.btn-secondary {
  @apply bg-gray-500 hover:bg-gray-600 text-white;
}

.btn-success {
  @apply bg-green-500 hover:bg-green-600 text-white;
}";
    }

    protected function createCssFile(): void
    {
        // Implementation of createCssFile method
    }
} 