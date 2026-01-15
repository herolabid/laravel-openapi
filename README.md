# Laravel OpenAPI

**Modern OpenAPI 3.1 documentation generator for Laravel 11+.**

A next-generation approach to API documentation using PHP 8.2+ attributes, replacing annotation-based tools with native language features. Built for developer experience with zero configuration, smart caching, and automatic module detection.

## Features

### Core Features
- **Zero Configuration** - Works out of the box with sensible defaults
- **PHP 8.2+ Attributes** - Native attribute-based syntax, no DocBlock annotations
- **Smart Caching** - 10x faster with intelligent file change detection
- **Dual UI Support** - Both Swagger UI and ReDoc included
- **Hot Reload** - Auto-regenerate specs during development

### Laravel Integration
- **Modular Architecture** - Auto-detects nwdart/laravel-modules and custom module structures
- **Auto-detect Security** - Automatically detects Laravel Sanctum and Passport configurations
- **FormRequest Integration** - Generate request schemas from validation rules
- **API Resource Support** - Extract response schemas from API Resources

### Code Quality
- **Clean Architecture** - SOLID principles and layered design
- **Well Tested** - 80%+ test coverage
- **Minimal Dependencies** - Lightweight and focused

## Requirements

- PHP 8.2 or higher
- Laravel 11.0 or higher

## Installation

```bash
composer require herolabid/laravel-openapi
```

That's it! The package auto-registers via Laravel's package discovery.

## Quick Start

### 1. Add Attributes to Your Controllers

```php
use App\Models\User;
use HerolabID\LaravelOpenApi\Attributes\{Get, Response, Parameter};

class UserController extends Controller
{
    #[Get(
        path: '/api/users/{id}',
        summary: 'Get user by ID',
        tags: ['Users']
    )]
    #[Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: ['type' => 'integer']
    )]
    #[Response(
        status: 200,
        description: 'User found',
        content: new Schema(ref: User::class)
    )]
    public function show(string $id)
    {
        return User::findOrFail($id);
    }
}
```

### 2. View Documentation

Visit your API documentation:

- **Swagger UI**: `http://your-app.test/api/docs/swagger`
- **ReDoc**: `http://your-app.test/api/docs/redoc`
- **JSON Spec**: `http://your-app.test/api/docs/spec.json`
- **YAML Spec**: `http://your-app.test/api/docs/spec.yaml`

### 3. Generate Spec (Optional)

```bash
php artisan openapi:generate
```

The spec is automatically generated on first request, but you can pre-generate it for production.

## Configuration

Publish the config file (optional):

```bash
php artisan vendor:publish --tag=openapi-config
```

Default configuration (`config/openapi.php`):

```php
return [
    'info' => [
        'title' => env('APP_NAME', 'API Documentation'),
        'version' => '1.0.0',
    ],

    'scan' => [
        'controllers' => [app_path('Http/Controllers')],
        'models' => [app_path('Models')],
    ],

    'modules' => [
        'enabled' => true,
        'auto_detect' => true,  // Auto-detect nwdart/laravel-modules
        'paths' => [],           // Custom module paths
    ],

    'cache' => [
        'enabled' => !app()->environment('local'),
        'ttl' => 3600,
    ],

    'hot_reload' => app()->environment('local'),

    'ui' => [
        'swagger' => true,
        'redoc' => true,
        'route_prefix' => 'api/docs',
    ],
];
```

### Modular Architecture Support

The package automatically detects and scans **nwdart/laravel-modules** and other modular structures.

#### Auto-Detection (nwdart/laravel-modules)

If you're using [nwdart/laravel-modules](https://github.com/nWidart/laravel-modules), the package will **automatically** discover all your modules:

```php
// config/openapi.php
'modules' => [
    'enabled' => true,
    'auto_detect' => true,  // ✅ Enabled by default
],
```

**Example structure:**
```
Modules/
├── User/
│   ├── Http/Controllers/UserController.php  ✅ Auto-discovered
│   └── Models/User.php                      ✅ Auto-discovered
├── Product/
│   ├── Http/Controllers/ProductController.php
│   └── Entities/Product.php                 ✅ Supports "Entities" folder
└── Order/
    ├── Controllers/OrderController.php      ✅ Alternative structure
    └── Models/Order.php
```

#### Custom Modular Paths

For custom module structures (not using nwdart), specify paths manually:

```php
// config/openapi.php
'modules' => [
    'enabled' => true,
    'auto_detect' => false,
    'paths' => [
        base_path('modules'),
        base_path('app/Modules'),
    ],
    'scan_paths' => [
        'controllers' => ['Http/Controllers', 'Controllers'],
        'models' => ['Models', 'Entities'],
    ],
],
```

#### Disable Module Scanning

If you don't use modules, you can disable it:

```php
'modules' => [
    'enabled' => false,
],
```

### Laravel FormRequest Integration

Automatically generate request body schemas from FormRequest validation rules:

```php
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'age' => 'integer|min:18|max:100',
            'role' => 'required|in:admin,user,guest',
        ];
    }
}
```

The package automatically converts validation rules to OpenAPI schema:

```yaml
CreateUserRequest:
  type: object
  required: [name, email, role]
  properties:
    name:
      type: string
      maxLength: 255
    email:
      type: string
      format: email
    age:
      type: integer
      minimum: 18
      maximum: 100
    role:
      type: string
      enum: [admin, user, guest]
```

### API Resource Integration

Extract response schemas from Laravel API Resources:

```php
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
        ];
    }
}
```

Automatically generates response schema with proper structure.

## Artisan Commands

```bash
# Generate OpenAPI spec
php artisan openapi:generate

# Clear cached spec
php artisan openapi:clear

# Validate spec
php artisan openapi:validate

# Serve with hot reload
php artisan openapi:serve
```

## Available Attributes

### Operation Attributes

```php
#[Get(path: '/api/users', summary: 'List users')]
#[Post(path: '/api/users', summary: 'Create user')]
#[Put(path: '/api/users/{id}', summary: 'Update user')]
#[Patch(path: '/api/users/{id}', summary: 'Partial update')]
#[Delete(path: '/api/users/{id}', summary: 'Delete user')]
```

### Parameter Attributes

```php
#[Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: ['type' => 'integer']
)]

#[Parameter(
    name: 'filter',
    in: 'query',
    schema: ['type' => 'string']
)]
```

### Request Body

```php
#[RequestBody(
    description: 'User data',
    required: true,
    content: new Schema(ref: User::class)
)]
```

### Response Attributes

```php
#[Response(
    status: 200,
    description: 'Success',
    content: new Schema(ref: User::class)
)]

#[Response(
    status: 404,
    description: 'Not found'
)]
```

### Schema Attributes (for Models)

```php
use HerolabID\LaravelOpenApi\Attributes\{Schema, Property};

#[Schema(title: 'User', description: 'User model')]
class User extends Model
{
    #[Property(type: 'integer', example: 1)]
    public int $id;

    #[Property(type: 'string', example: 'John Doe')]
    public string $name;

    #[Property(type: 'string', format: 'email')]
    public string $email;
}
```

## Architecture

This package follows clean code principles and layered architecture:

```
┌─────────────────────────────────────────────┐
│     Presentation Layer (UI, Controllers)     │
├─────────────────────────────────────────────┤
│   Application Layer (Commands, Services)    │
├─────────────────────────────────────────────┤
│      Domain Layer (Attributes, Builders)     │
├─────────────────────────────────────────────┤
│  Infrastructure Layer (Cache, File I/O)     │
└─────────────────────────────────────────────┘
```

### Key Design Patterns

- **Builder Pattern** - Spec generation
- **Strategy Pattern** - Multiple UI renderers
- **Repository Pattern** - Cache access
- **Factory Pattern** - Parser creation
- **Dependency Injection** - All services use constructor injection

## Performance

- **First generation**: < 500ms for 50 routes
- **Cached retrieval**: < 10ms
- **Hot reload detection**: < 50ms
- **Memory usage**: < 10MB

Approximately **10x faster** than L5-Swagger with caching enabled.

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Static analysis
composer analyse

# Code formatting
composer format
```

## Testing

This package maintains 80%+ test coverage with:

- **Unit Tests** - Individual class testing
- **Integration Tests** - Multi-component testing
- **Feature Tests** - End-to-end testing

```bash
composer test
```

## Code Quality

- PHPStan Level 8
- PSR-12 Code Style
- SOLID Principles
- Comprehensive PHPDoc

## Why Choose Laravel OpenAPI?

### vs L5-Swagger

| Feature | L5-Swagger | Laravel OpenAPI |
|---------|-----------|-----------------|
| Syntax | DocBlock annotations | PHP 8.2+ Attributes |
| Config Lines | 318 | ~50 |
| Dependencies | 5+ packages | 2 core packages |
| Installation | Multi-step process | Single command |
| Caching | Manual | Smart + Auto-invalidation |
| Hot Reload | No | Yes |
| UI Options | Swagger only | Swagger + ReDoc |
| Module Support | Manual config | Auto-detection |
| FormRequest | No | Auto-generate schemas |
| API Resource | No | Auto-extract schemas |
| Security Detection | Manual | Auto (Sanctum/Passport) |

### Key Advantages

1. **Native PHP Attributes** - No external annotation syntax, just PHP
2. **Zero Config** - Works immediately after installation
3. **Smart Caching** - Automatically invalidates when files change
4. **Module-Aware** - Detects nwdart/laravel-modules automatically
5. **Laravel-Native** - Deep integration with FormRequests and Resources

## Roadmap

### Completed ✓
- [x] Core attribute system
- [x] OpenAPI 3.1 spec generation
- [x] Dual UI (Swagger + ReDoc)
- [x] Smart caching with auto-invalidation
- [x] Modular architecture support
- [x] Security auto-detection
- [x] FormRequest schema generation
- [x] API Resource schema extraction

### Planned
- [ ] Request/Response examples from tests
- [ ] Interactive API playground
- [ ] Improved type inference
- [ ] Enhanced validation rules parsing

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email irfan26arsyad@gmail.com instead of using the issue tracker.

## Credits

- **Author**: Irfan Arsyad
- **Inspired by**: L5-Swagger

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
