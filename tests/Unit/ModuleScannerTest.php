<?php

use Kwidoo\HexTools\Config\HexToolsConfig;
use Kwidoo\HexTools\Scanners\ClassNameResolver;
use Kwidoo\HexTools\Scanners\ModuleScanner;
use Kwidoo\HexTools\Support\Filesystem;

function createFixtures(string $appPath, string $module): void
{
    $dirs = [
        $appPath . "/Domain/{$module}/Entities",
        $appPath . "/Application/{$module}/UseCases",
        $appPath . '/Http/Controllers',
        $appPath . "/Infrastructure/{$module}/Persistence",
        $appPath . '/Models',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    file_put_contents($appPath . "/Domain/{$module}/Entities/{$module}.php", '<?php');
    file_put_contents($appPath . "/Application/{$module}/UseCases/Create{$module}UseCase.php", '<?php');
    file_put_contents($appPath . "/Http/Controllers/{$module}Controller.php", '<?php');
    file_put_contents($appPath . "/Infrastructure/{$module}/Persistence/Eloquent{$module}Repository.php", '<?php');
    file_put_contents($appPath . "/Models/{$module}.php", '<?php');
}

it('scans all layers for a module', function () {
    $appPath = sys_get_temp_dir() . '/hex-scan-' . uniqid();
    mkdir($appPath, 0755, true);
    createFixtures($appPath, 'Product');

    $config = new HexToolsConfig([
        'namespace' => 'App',
        'paths' => ['app' => $appPath],
        'module_rules' => ['Product' => ['Product', 'Shared']],
        'modules' => ['Product', 'Shared'],
    ]);
    $filesystem = new Filesystem();
    $resolver = new ClassNameResolver($appPath, 'App');
    $scanner = new ModuleScanner($config, $filesystem, $resolver);

    $result = $scanner->scan('Product');

    expect($result)->toHaveKey('Domain')
        ->and($result)->toHaveKey('Application')
        ->and($result)->toHaveKey('Http')
        ->and($result)->toHaveKey('Infrastructure')
        ->and($result)->toHaveKey('Models');

    expect($result['Domain'])->toContain('App\Domain\Product\Entities\Product');
    expect($result['Http'])->toContain('App\Http\Controllers\ProductController');

    exec('rm -rf ' . escapeshellarg($appPath));
});

it('returns empty layers when directories do not exist', function () {
    $config = new HexToolsConfig([
        'namespace' => 'App',
        'paths' => ['app' => '/nonexistent/path'],
        'modules' => [],
        'module_rules' => [],
    ]);
    $filesystem = new Filesystem();
    $resolver = new ClassNameResolver('/nonexistent/path', 'App');
    $scanner = new ModuleScanner($config, $filesystem, $resolver);

    $result = $scanner->scan('Product');

    expect($result)->toBeEmpty();
});

it('matches models with prefix pattern', function () {
    $appPath = sys_get_temp_dir() . '/hex-scan-' . uniqid();
    mkdir($appPath, 0755, true);

    $modelsDir = $appPath . '/Models';
    if (!is_dir($modelsDir)) {
        mkdir($modelsDir, 0755, true);
    }

    // Create model files
    file_put_contents($modelsDir . '/Product.php', '<?php namespace App\Models; class Product {}');
    file_put_contents($modelsDir . '/ProductVariant.php', '<?php namespace App\Models; class ProductVariant {}');
    file_put_contents($modelsDir . '/ProductImage.php', '<?php namespace App\Models; class ProductImage {}');
    file_put_contents($modelsDir . '/Order.php', '<?php namespace App\Models; class Order {}');

    $config = new HexToolsConfig([
        'namespace' => 'App',
        'paths' => ['app' => $appPath],
        'modules' => ['Product', 'Order'],
        'module_rules' => [],
    ]);
    $filesystem = new Filesystem();
    $resolver = new ClassNameResolver($appPath, 'App');
    $scanner = new ModuleScanner($config, $filesystem, $resolver);

    $result = $scanner->scan('Product');

    expect($result['Models'])->toContain('App\\Models\\Product')
        ->and($result['Models'])->toContain('App\\Models\\ProductVariant')
        ->and($result['Models'])->toContain('App\\Models\\ProductImage')
        ->and($result['Models'])->not->toContain('App\\Models\\Order');

    exec('rm -rf ' . escapeshellarg($appPath));
});
