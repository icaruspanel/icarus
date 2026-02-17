# Laravel Starter Kit

An opinionated Laravel starter kit by [Ollie Read](https://github.com/ollieread), designed to skip the boilerplate process of setting up a new Laravel project.

## Requirements

- PHP 8.5+
- Composer
- Docker
- Node.js & NPM

## Installation

### Using the Laravel Installer

```bash
laravel new your-project-name --using=ollieread/laravel-starter
```

### Using Composer

```bash
composer create-project ollieread/laravel-starter your-project-name
cd your-project-name
composer setup
```

## Quick Start

Start the development environment with a single command:

```bash
composer dev
```

This runs concurrently:
- Docker containers via Sail (`sail up`) - includes queue worker
- Log viewer via Pail (`php artisan pail`)
- Vite dev server (`npm run dev`)

## What's Different?

This starter kit makes several opinionated changes from the default Laravel installation:

### Routing

Routes are no longer defined in the `routes/` directory. Instead, they use an object-first approach with classes implementing `App\Support\RouteMapper`:

```php
// app/Http/Routes/DefaultRoutes.php
final class DefaultRoutes implements RouteMapper
{
    public function map(Router $router): void
    {
        $router->get('/', fn () => view('welcome'));
    }
}
```

Route mappers are registered in `app/Boot/ConfigureRouting.php`:

```php
private static array $mappers = [
    'web' => [
        DefaultRoutes::class,
    ],
    // Add 'api' => [...] for API routes
];
```

### Application Bootstrap

The `app/Boot/` directory contains classes for configuring:
- `ConfigureRouting.php` - Route registration
- `ConfigureMiddleware.php` - Middleware configuration
- `ConfigureExceptions.php` - Exception handling

### Configuration

- All framework config files are published to `config/`
- The framework is told **not** to merge default configuration (`dontMergeFrameworkConfiguration()`)
- This gives you explicit control over every configuration value

### Database

- **PostgreSQL** is the default database
- The `tpetry/laravel-postgresql-enhanced` package is installed for enhanced PostgreSQL support
- Migration stubs use PostgreSQL-enhanced Blueprint for additional column types and features
- SQLite, MySQL, and MariaDB configurations are also available

### Frontend

- **SCSS** instead of Tailwind CSS
- Entry point: `resources/scss/app.scss`
- Vite configured with SCSS compilation
- Axios pre-configured with CSRF token handling

### Development Tools

Pre-installed and configured:
- **Telescope** - Debugging and monitoring (non-production only)
- **IDE Helper** - Auto-generates IDE metadata for autocompletion
- **Pail** - Elegant log viewer
- **Larastan** - Static analysis at maximum level

### Other Changes

- Default migrations removed (except Telescope)
- Stubs published to `stubs/`
- Daily log rotation enabled
- Sessions, cache default to file-based storage
- Queue defaults to sync processing
- `app/Http/Controllers` directory removed

## Available Commands

### Composer Scripts

| Command | Description |
|---------|-------------|
| `composer setup` | Full project initialization |
| `composer dev` | Run concurrent development servers |
| `composer test` | Run test suite |
| `composer static` | Run PHPStan static analysis |
| `composer ide-helper` | Generate IDE helper files |
| `composer ide-helper:models` | Generate model mixins |

### NPM Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Start Vite development server |
| `npm run build` | Build for production |

## Directory Structure

```
app/
├── Boot/                    # Application bootstrap configuration
│   ├── ConfigureRouting.php
│   ├── ConfigureMiddleware.php
│   └── ConfigureExceptions.php
├── Http/
│   └── Routes/              # Route mapper classes
│       └── DefaultRoutes.php
├── Models/
├── Providers/
│   ├── AppServiceProvider.php
│   ├── DevServiceProvider.php      # Development-only providers
│   └── TelescopeServiceProvider.php
└── Support/
    └── RouteMapper.php      # Route mapper interface

resources/
├── js/
│   ├── app.js
│   └── bootstrap.js
├── scss/
│   └── app.scss
└── views/

stubs/                       # Published stub files
```

## Adding Routes

1. Create a new route mapper in `app/Http/Routes/`:

```php
<?php
declare(strict_types=1);

namespace App\Http\Routes;

use App\Support\RouteMapper;
use Illuminate\Routing\Router;

final class ApiRoutes implements RouteMapper
{
    public function map(Router $router): void
    {
        $router->prefix('api')->group(function (Router $router) {
            // Your API routes here
        });
    }
}
```

2. Register it in `app/Boot/ConfigureRouting.php`:

```php
private static array $mappers = [
    'web' => [DefaultRoutes::class],
    'api' => [ApiRoutes::class],
];
```

3. Add the mapping method if needed:

```php
private function mapApi(Router $router, array $mappers): void
{
    foreach ($mappers as $mapper) {
        (new $mapper)->map($router);
    }
}
```

## Static Analysis

This starter kit enforces maximum PHPStan level. Run analysis with:

```bash
composer static
```

Configuration is in `phpstan.neon`.

## Docker Support

Laravel Sail is included and installed automatically when running `composer setup`.

## Testing

```bash
composer test
```

Tests run with:
- In-memory SQLite database
- Array cache driver
- Sync queue processing
- Telescope disabled

## IDE Support

Run after adding/modifying models:

```bash
composer ide-helper
composer ide-helper:models
```

This generates `_ide_helper.php`, `.phpstorm.meta.php`, and model mixins for better autocompletion.

## License

MIT License. See [LICENSE](LICENSE) for details.
