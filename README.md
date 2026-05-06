# Hex Tools

[![CI](https://github.com/kwidoo/hex-tools/actions/workflows/ci.yml/badge.svg)](https://github.com/kwidoo/hex-tools/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012-red.svg)](https://laravel.com)

`kwidoo/hex-tools` is a pragmatic Laravel/PHP toolkit for generating, inspecting, documenting, and enforcing hexagonal architecture boundaries.

**Supported Versions:** Laravel 11, 12 | PHP 8.2+

> **TODO:** Laravel 13 support is planned for a future release.

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
| `hex:inspect` | Inspect modules, layers, dependencies, and architecture issues |
| `hex:doctor` | Check package configuration and quality-tooling health |
| `hex:docs` | Generate architecture intelligence documentation |
| `hex:baseline` | Baseline current architecture issues for legacy projects |
| `hex:explain {rule}` | Explain a known architecture rule code |
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

### Module Configuration

The package uses a neutral default configuration with example modules. You should replace these with your own business domain modules:

```php
// config/hex-tools.php

'modules' => [
    // Define your business modules here
    'User',
    'Product',
    'Order',
    'Payment',
    'Shared',  // Common utilities shared across modules
],

'module_rules' => [
    // Define which modules each module can depend on
    'Shared' => ['Shared'],
    'User' => ['User', 'Shared'],
    'Product' => ['Product', 'Shared'],
    'Order' => ['Order', 'Product', 'User', 'Shared'],
    'Payment' => ['Payment', 'Order', 'User', 'Shared'],
],
```

**Guidelines:**
- Each module represents a bounded context in your domain
- The `Shared` module is typically used for common code that other modules can depend on
- A module may depend on itself implicitly (no need to list it)
- Avoid circular dependencies between modules

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

## Architecture Intelligence

```bash
php artisan hex:inspect --format=json
php artisan hex:inspect --module=Product --fail-on-violations
php artisan hex:doctor --strict
php artisan hex:docs --output=docs/architecture --force
php artisan hex:baseline --output=.hex/baseline/architecture.json
php artisan hex:explain domain_depends_on_framework
```

`hex:inspect` analyzes source structure and reports conservative rule violations such as Domain depending on framework or Infrastructure code. `hex:doctor` checks setup health, including config files, quality tooling, CI workflow, module paths, and composer scripts. `hex:baseline` stores stable hashes for current issues so future inspections can distinguish existing baseline issues from new ones.

## PHPMD

Hex Tools can generate a Laravel-friendly PHPMD ruleset.

Install PHPMD:

```bash
composer require --dev phpmd/phpmd
```

Generate config:

```bash
php artisan hex:phpmd:install
php artisan hex:phpmd:install --composer-scripts
```

Run:

```bash
composer md
composer md:domain
composer md:application
```

Generate baseline for existing projects:

```bash
composer md:baseline
```

| Command | Description |
|---|---|
| `hex:phpmd:install` | Install PHPMD ruleset and optional composer scripts |
| `hex:phpmd:generate` | Regenerate PHPMD ruleset |
| `hex:phpmd:baseline` | Print or run PHPMD baseline generation |
| `hex:phpmd:run` | Run PHPMD using generated rules |

## PHPStan/Larastan

Hex Tools can generate PHPStan/Larastan configs for Laravel Hexagonal Architecture projects.

```bash
composer require --dev larastan/larastan

php artisan hex:phpstan:install
php artisan hex:phpstan:install --composer-scripts
```

Generated configs:

* `phpstan.neon.dist`
* `phpstan-domain.neon`
* `phpstan-application.neon`

Run:

```bash
composer stan
composer stan:domain
composer stan:application
```

| Command | Description |
|---|---|
| `hex:phpstan:install` | Install PHPStan configs and optional composer scripts |
| `hex:phpstan:generate` | Regenerate PHPStan config files |
| `hex:phpstan:baseline` | Print or run PHPStan baseline generation |

## License

MIT
