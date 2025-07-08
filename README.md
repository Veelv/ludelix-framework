# Ludelix Framework

Modern PHP Framework with Multi-Tenancy, Advanced ORM, SPA Integration, and Professional Architecture.

## 🚀 Features

### Core Framework
- **Modern PHP 8.1+** - Latest PHP features and performance
- **Multi-Tenancy** - Built-in multi-tenant architecture
- **Advanced ORM** - Powerful database abstraction with attributes
- **SPA Integration** - Seamless integration with ludelix-connect
- **WebSocket Support** - Real-time communication
- **GraphQL** - Modern API development
- **Asset Management** - Vite integration with hot reload
- **Template Engine** - Ludou templates with hot reload

### Architecture
- **Dependency Injection** - Professional DI container
- **Event System** - Robust event dispatcher
- **Caching** - Multi-driver cache system
- **Queue System** - Background job processing
- **Security** - Built-in security features
- **Observability** - Monitoring and tracing
- **Plugin System** - Extensible architecture

### Developer Experience
- **Brow CLI** - Powerful command-line interface
- **Code Generation** - Automatic scaffolding
- **Hot Reload** - Development with instant feedback
- **Professional Structure** - Enterprise-ready organization
- **Type Safety** - Full PHP type declarations

## 📦 Installation

```bash
composer require ludelix/framework
```

## 🏗️ Quick Start

### 1. Create New Project

```bash
composer create-project ludelix/framework my-app
cd my-app
```

### 2. Generate Components

```bash
# Generate complete module
php brow kria:module User

# Generate specific components
php brow kria:repository Product
php brow kria:service Order
php brow kria:entity Category
```

### 3. File Structure

```
app/
├── Repositories/
│   ├── user.repository.php      # UserRepository
│   └── product.repository.php   # ProductRepository
├── Services/
│   ├── user.service.php         # UserService
│   └── product.service.php      # ProductService
├── Entities/
│   ├── user.entity.php          # User
│   └── product.entity.php       # Product
├── Jobs/
│   └── user.job.php             # UserJob
├── Console/
│   └── user.console.php         # UserCommand
└── Middleware/
    └── user.middleware.php      # UserMiddleware
```

## 🎯 Usage Examples

### Entity with Attributes

```php
<?php

use Ludelix\Database\Attributes\Entity;
use Ludelix\Database\Attributes\Column;
use Ludelix\Database\Attributes\PrimaryKey;

#[Entity(table: 'users')]
class User
{
    #[PrimaryKey]
    #[Column(type: 'int', autoIncrement: true)]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'string', length: 255, unique: true)]
    public string $email;

    #[Column(type: 'datetime')]
    public DateTime $createdAt;
}
```

### Repository Pattern

```php
<?php

use Ludelix\Database\Core\Repository;

class UserRepository extends Repository
{
    protected function getEntityClass(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function getActive(): array
    {
        return $this->findBy(['active' => true]);
    }
}
```

### Service Layer

```php
<?php

class UserService
{
    public function __construct(
        private UserRepository $repository
    ) {}

    public function createUser(array $data): User
    {
        $this->validateData($data);
        
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->createdAt = new DateTime();
        
        return $this->repository->save($user);
    }
}
```

### Ludou Templates

```html
#extends['layouts/app']

#section['content']
<div class="container">
    <h1>Welcome, #[t('welcome', ['name' => $user->name])]!</h1>
    
    <script src="#[asset('js/app.js')]"></script>
    <link rel="stylesheet" href="#[asset('css/app.css')]">
    
    #foreach($posts as $post)
        <article>
            <h2>#[$post->title]</h2>
            <p>#[$post->content]</p>
        </article>
    #endforeach
</div>
#endsection
```

### SPA Integration

```php
<?php

use Ludelix\Connect\Connect;

class HomeController
{
    public function index()
    {
        return Connect::render('Home', [
            'user' => auth()->user(),
            'posts' => Post::latest()->get(),
            'shared' => [
                'app' => ['name' => 'Ludelix App']
            ]
        ]);
    }
}
```

### Multi-Tenancy

```php
<?php

use Ludelix\Tenant\Core\Tenant;
use Ludelix\Tenant\Attributes\TenantScoped;

#[Entity(table: 'products')]
#[TenantScoped]
class Product
{
    #[PrimaryKey]
    public int $id;
    
    #[Column(type: 'string')]
    public string $name;
    
    // Automatically filtered by current tenant
}
```

### Background Jobs

```php
<?php

use Ludelix\Queue\Core\Job;

class SendEmailJob extends Job
{
    protected string $queue = 'emails';
    protected int $maxTries = 3;

    public function handle(): void
    {
        $data = $this->getData();
        
        // Send email logic
        $this->sendEmail($data['to'], $data['subject'], $data['body']);
    }
}

// Dispatch job
$job = new SendEmailJob();
$job->setData(['to' => 'user@example.com', 'subject' => 'Hello']);
$job->dispatch();
```

### WebSocket Integration

```php
<?php

use Ludelix\WebSocket\Core\WebSocketServer;

$server = new WebSocketServer([
    'host' => '0.0.0.0',
    'port' => 8080
]);

$server->on('message', function($connection, $data) {
    // Broadcast to all connections
    $server->broadcast($data);
});

$server->start();
```

## 🛠️ Brow CLI Commands

### Module Generation
```bash
php brow kria:module User          # Complete module
php brow kria:repository Product   # Repository only
php brow kria:service Order        # Service only
php brow kria:entity Category      # Entity only
php brow kria:job EmailSender      # Background job
php brow kria:middleware Auth      # Middleware
php brow kria:console Cleanup      # Console command
```

### Development
```bash
php brow serve                     # Start development server
php brow migrate                   # Run migrations
php brow cache:clear               # Clear cache
php brow queue:work                # Process queue jobs
```

## 🏗️ Architecture

### Directory Structure
```
ludelix-framework/
├── src/
│   ├── Asset/              # Asset management
│   ├── Bridge/             # Service bridge
│   ├── Cache/              # Caching system
│   ├── Connect/            # SPA integration
│   ├── Core/               # Framework core
│   ├── Database/           # ORM and database
│   ├── GraphQL/            # GraphQL support
│   ├── Ludou/              # Template engine
│   ├── Queue/              # Background jobs
│   ├── Routing/            # URL routing
│   ├── Security/           # Security features
│   ├── Tenant/             # Multi-tenancy
│   ├── Translation/        # Internationalization
│   └── WebSocket/          # Real-time communication
├── bin/
│   └── brow                # CLI executable
└── composer.json
```

### Key Components

#### 1. **Bridge System**
- Centralized service access
- Dependency injection
- Context management

#### 2. **Advanced ORM**
- Attribute-based mapping
- Repository pattern
- Multi-tenant support
- Query builder

#### 3. **Ludou Templates**
- Modern template syntax
- Hot reload support
- Asset integration
- Translation support

#### 4. **Asset Management**
- Vite integration
- Hot module replacement
- Automatic versioning
- Multi-format support

#### 5. **Multi-Tenancy**
- Automatic tenant resolution
- Data isolation
- Tenant-aware caching
- Database separation

## 🔧 Configuration

### Database
```php
<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
        ]
    ]
];
```

### Cache
```php
<?php
return [
    'default' => 'redis',
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', 'localhost'),
            'port' => env('REDIS_PORT', 6379),
        ]
    ]
];
```

## 🧪 Testing

```bash
# Run tests
composer test

# Run with coverage
composer test-coverage

# Code analysis
composer analyse

# Code style check
composer cs-check

# Fix code style
composer cs-fix

# Run all quality checks
composer quality
```

## 📚 Documentation

- [Getting Started](docs/getting-started.md)
- [Architecture Guide](docs/architecture.md)
- [Database & ORM](docs/database.md)
- [Multi-Tenancy](docs/multi-tenancy.md)
- [Template Engine](docs/templates.md)
- [Asset Management](docs/assets.md)
- [API Reference](docs/api.md)

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🏆 Credits

Built with ❤️ by the Ludelix Team

---

**Ludelix Framework** - Modern PHP development made elegant and powerful.