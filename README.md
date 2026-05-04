# Hex Tools

[![CI](https://github.com/kwidoo/hex-tools/actions/workflows/ci.yml/badge.svg)](https://github.com/kwidoo/hex-tools/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012-red.svg)](https://laravel.com)

Laravel development tools for Hexagonal / Clean Architecture projects.

## Installation

```bash
composer require --dev kwidoo/hex-tools
composer require --dev deptrac/deptrac

php artisan hex:install
php artisan hex:deptrac:layers
php artisan hex:deptrac:modules
```

## Commands

| Command | Description |
|---|---|
| `hex:install` | Publish config, create docs/build folders, optionally install composer scripts |
| `hex:deptrac:layers` | Generate `deptrac.layers.yaml` for strict technical layer checks |
| `hex:deptrac:modules` | Generate `deptrac.modules.yaml` for business module boundary checks |
| `hex:map {module}` | Print grouped class map for a module |
| `hex:module:init {module}` | Create module folder skeleton and docs stub |
| `hex:docs:generate` | Generate architecture documentation from config |
| `hex:adr:create {title}` | Create a numbered Architecture Decision Record |

## Layer config vs Module config

**`deptrac.layers.yaml`** enforces technical layer boundaries:

- `Domain` may only depend on itself
- `Application` may depend on `Domain`
- `Http` may depend on `Application`, `Domain`, framework, and vendor
- `Infrastructure` may depend on `Application`, `Domain`, `Models`, framework, and vendor
- `Models` may depend on `Domain`, framework, and vendor

This prevents technical violations like `Http -> Models` or `Domain -> Eloquent`.

**`deptrac.modules.yaml`** enforces business module boundaries:

Each module (e.g. `Product`, `Order`) is collected from all technical layers but treated as a single unit. Rules define which modules may depend on which others.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=hex-tools-config
```

Edit `config/hex-tools.php` to define your modules and allowed dependencies.

## Example

```bash
# Show architecture map for Product module
php artisan hex:map Product

# Generate architecture checks
composer hex:layers
composer hex:modules

# Inspect a specific module membership in Deptrac
vendor/bin/deptrac debug:layer --config-file=deptrac.modules.yaml Product

# Check if Product depends on Pickup
vendor/bin/deptrac debug:dependencies --config-file=deptrac.modules.yaml Product Pickup

# Find unassigned classes
vendor/bin/deptrac debug:unassigned --config-file=deptrac.modules.yaml

# Create a new module skeleton
php artisan hex:module:init NewModule --with-folders

# Create an Architecture Decision Record
php artisan hex:adr:create "Domain must not use Eloquent" --status=accepted
```

## License

MIT
