<div align="center">
  <h1>🚀 Ludelix Framework</h1>
  <p><strong>Modern PHP framework for next-generation web applications</strong></p>
  
  [![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
  [![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
  [![Tests](https://img.shields.io/badge/Tests-Passing-brightgreen.svg)](#testing)
  [![Version](https://img.shields.io/badge/Version-1.0.0-orange.svg)](#)
</div>

---

## ✨ Key Features

### 🎨 **SharpTemplate Engine**
- Reactive templates with `.ludou` extension
- Hot reload support for development
- Built-in security and performance optimizations

### 🌉 **Bridge System**
- Contextual service access without traditional facades
- Type-safe service resolution
- Better performance and debugging

### 🔗 **LudelixConnect**
- Modern SPA integration replacing Inertia.js
- Server-Side Rendering (SSR) support
- WebSocket integration for real-time features
- React, Vue, and Svelte adapters

### 🏢 **Multi-Tenancy**
- Native tenant isolation
- Database, cache, and configuration separation
- Automatic tenant resolution

### 📊 **Observability**
- Built-in OpenTelemetry integration
- Prometheus metrics collection
- Performance monitoring and tracing

### 🛣️ **Dynamic Routing**
- YAML and PHP route definitions
- REST, GraphQL, and WebSocket unified routing
- Automatic API documentation generation

---

## 🚀 Quick Start

### Installation

```bash
# Install the framework
composer require ludelix/framework

# Create a new project
composer create-project ludelix/app my-app
cd my-app

# Install frontend dependencies
npm install

# Start development
php ludelix serve
```

### Basic Usage

```php
<?php
// routes.php
Route::get('/', [HomeController::class, 'index'])
    ->connect('Home')
    ->name('home');

// HomeController.php
class HomeController {
    public function index() {
        return Connect::component('Home', [
            'user' => Bridge::auth()->user(),
            'posts' => Bridge::db()->table('posts')->get()
        ]);
    }
}
```

```javascript
// Home.jsx
import { get, form } from '@ludelix/connect';

function Home({ user, posts }) {
    return (
        <div>
            <h1>Welcome, {user.name}!</h1>
            <button onClick={() => get('/dashboard')}>Dashboard</button>
        </div>
    );
}
```

---

## 📚 Documentation

- **[Getting Started](https://ludelix.com/docs/getting-started)**
- **[SharpTemplate Guide](https://ludelix.com/docs/templates)**
- **[Bridge System](https://ludelix.com/docs/bridge)**
- **[LudelixConnect](https://ludelix.com/docs/connect)**
- **[Multi-Tenancy](https://ludelix.com/docs/tenancy)**
- **[API Reference](https://ludelix.com/docs/api)**

---

## 🧪 Testing

```bash
# Run tests
composer test

# Run with coverage
composer test -- --coverage

# Static analysis
composer analyse
```

---

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

```bash
git clone https://github.com/ludelix/framework.git
cd framework
composer install
composer test
```

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🌟 Why Ludelix?

### Modern Architecture
- **SharpTemplate (.ludou)** - Reactive templates with hot reload
- **Bridge System** - Type-safe contextual service access
- **LudelixConnect** - Advanced SPA integration with SSR
- **Native Multi-Tenancy** - Built-in tenant isolation
- **Integrated Observability** - OpenTelemetry and Prometheus ready
- **Unified Frontend** - Single Vite configuration for all frameworks

---

<div align="center">
  <p>Made with ❤️ by the Ludelix Team</p>
  <p>
    <a href="https://ludelix.com">Website</a> •
    <a href="https://ludelix.com/docs">Documentation</a> •
    <a href="https://github.com/ludelix/framework/issues">Issues</a> •
    <a href="https://discord.gg/ludelix">Discord</a>
  </p>
</div>