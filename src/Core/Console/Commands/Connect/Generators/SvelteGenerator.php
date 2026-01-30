<?php

namespace Ludelix\Core\Console\Commands\Connect\Generators;

class SvelteGenerator extends BaseGenerator
{
  public function generate(array $options): void
  {
    $this->options = $options;

    // Create frontend structure
    $this->createFrontendStructure();

    // Create configuration files
    $this->createConfigurationFiles();

    // Create Svelte components
    $this->createSvelteComponents();

    // Create templates
    $this->createTemplates();

    // Create routes
    $this->createRoutes();
  }

  protected function createFrontendStructure(): void
  {
    $this->createDirectory($this->projectRoot . '/frontend/js/components');
    $this->createDirectory($this->projectRoot . '/frontend/js/pages');
    $this->createDirectory($this->projectRoot . '/frontend/css');
  }

  protected function createConfigurationFiles(): void
  {
    // Update package.json with Svelte dependencies
    $dependencies = [
      'svelte' => '^4.0.0',
      'ludelix-connect' => '^1.0.0'
    ];

    $devDependencies = [
      '@sveltejs/vite-plugin-svelte' => '^2.4.0',
      'vite' => '^4.4.0'
    ];

    if ($this->options['typescript']) {
      $devDependencies['typescript'] = '^5.0.0';
      $devDependencies['@tsconfig/svelte'] = '^5.0.0';
    }

    if ($this->options['tailwind']) {
      $devDependencies['tailwindcss'] = '^4.0.0';
      $devDependencies['@tailwindcss/vite'] = '^4.0.0';
      // Autoprefixer and PostCSS are not needed for Tailwind v4's CSS-first approach
    }

    $this->updatePackageJson($dependencies, $devDependencies);

    // Create Vite config
    $this->createViteConfig('svelte', $this->options);

    // Create TypeScript config
    if ($this->options['typescript']) {
      $this->createTsConfig();
    }

    // Create Tailwind config
    // Tailwind v4 uses CSS-first configuration, so we only need the CSS file
    if ($this->options['tailwind']) {
      $this->createCssFile();
    }
  }

  protected function createSvelteComponents(): void
  {
    // Create App component
    $appComponent = $this->getAppComponent();
    $this->writeFile($this->projectRoot . '/frontend/js/components/App.svelte', $appComponent);

    // Create Layout component
    $layoutComponent = $this->getLayoutComponent();
    $this->writeFile($this->projectRoot . '/frontend/js/components/Layout.svelte', $layoutComponent);

    // Create pages
    $this->createPages();

    // Create ludelix-connect config
    $connectConfig = $this->getConnectConfig();
    $this->writeFile($this->projectRoot . '/frontend/js/ludelix-connect.js', $connectConfig);
  }

  protected function createPages(): void
  {
    $pages = [
      'Home.svelte' => $this->getHomePage(),
      'About.svelte' => $this->getAboutPage(),
      'Contact.svelte' => $this->getContactPage()
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
    $plugins = "svelte()";
    if ($options['tailwind']) {
      $plugins .= ",\n    tailwindcss()";
    }

    return "import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    {$plugins}
  ],
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
})";
  }

  protected function getTailwindConfig(): string
  {
    return "/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './frontend/js/**/*.{js,svelte,ts}',
    './templates/**/*.ludou'
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}";
  }

  protected function getTsConfig(): string
  {
    return '{
  "extends": "@tsconfig/svelte/tsconfig.json",
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

  protected function getPostCssConfig(): string
  {
    return "export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}";
  }

  protected function getCssContent(): string
  {
    return '@import "tailwindcss";';
  }

  protected function getConnectConfigFile(): string
  {
    return "<?php

return [
    'enabled' => true,
    'ssr' => true,
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
];";
  }

  // Component methods
  protected function getAppComponent(): string
  {
    return '<script>
  import Layout from "./Layout.svelte"
</script>

<Layout>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-4">Welcome to Ludelix</h1>
    <p class="text-gray-600">Your modern PHP + Svelte application</p>
  </div>
</Layout>

<style>
  /* Component styles */
</style>';
  }

  protected function getLayoutComponent(): string
  {
    return '<script>
  // Layout component
</script>

<div class="min-h-screen bg-gray-50">
  <nav class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4">
      <h1 class="text-xl font-semibold">Ludelix App</h1>
    </div>
  </nav>
  <main class="py-8">
    <slot />
  </main>
</div>

<style>
  /* Layout styles */
</style>';
  }

  protected function getHomePage(): string
  {
    return '<script>
  // Home page component
</script>

<div>
  <h1 class="text-2xl font-bold mb-4">Home</h1>
  <p>Welcome to your Ludelix application!</p>
</div>

<style>
  /* Home page styles */
</style>';
  }

  protected function getAboutPage(): string
  {
    return '<script>
  // About page component
</script>

<div>
  <h1 class="text-2xl font-bold mb-4">About</h1>
  <p>This is the about page.</p>
</div>

<style>
  /* About page styles */
</style>';
  }

  protected function getContactPage(): string
  {
    return '<script>
  // Contact page component
</script>

<div>
  <h1 class="text-2xl font-bold mb-4">Contact</h1>
  <p>Get in touch with us.</p>
</div>

<style>
  /* Contact page styles */
</style>';
  }

  protected function getConnectConfig(): string
  {
    return "import { init } from 'ludelix-connect'

init({
  el: '#app',
  resolve: (name) => {
    const pages = import.meta.glob('./pages/*.svelte', { eager: true })
    return pages[`./pages/\${name}.svelte`]
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
    #[asset(\'css/app.css\')]
    <meta name="csrf-token" content="#[csrf_token()]">
</head>
<body>
    #[connect]
    #[asset(\'js/app.js\')]
</body>
</html>';
  }

  protected function getWelcomeTemplate(): string
  {
    return '#extends[\'layouts.app\']

#section content
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-4">Welcome to Ludelix</h1>
    <p class="text-gray-600">Your modern PHP application</p>
</div>
#endsection';
  }

  protected function getHomeTemplate(): string
  {
    return '#extends[\'layouts.app\']

#section content
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Home</h1>
    <p>Welcome to your Ludelix application!</p>
</div>
#endsection';
  }

  protected function getAboutTemplate(): string
  {
    return '#extends[\'layouts.app\']

#section content
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">About</h1>
    <p>This is the about page.</p>
</div>
#endsection';
  }

  // Route methods
  protected function getWebRoutes(): string
  {
    return '<?php

use Ludelix\Bridge\Bridge;
use Ludelix\Http\Request;

Bridge::route()->get(\'/\', function (Request $request) {
    return Bridge::ludou()->render(\'welcome\');
});

Bridge::route()->get(\'/home\', function (Request $request) {
    return Bridge::ludou()->render(\'home\');
});

Bridge::route()->get(\'/about\', function (Request $request) {
    return Bridge::ludou()->render(\'about\');
});';
  }

  protected function getApiRoutes(): string
  {
    return '<?php

use Ludelix\Bridge\Bridge;
use Ludelix\Http\Request;

// API routes for SPA
Bridge::route()->get(\'/api/home\', function (Request $request) {
    return Bridge::connect()->render(\'Home\', [
        \'user\' => Bridge::auth()->user(),
        \'posts\' => []
    ]);
});

Bridge::route()->get(\'/api/about\', function (Request $request) {
    return Bridge::connect()->render(\'About\', [
        \'title\' => \'About Us\'
    ]);
});';
  }
}