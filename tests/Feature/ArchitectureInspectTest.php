<?php

use Kwidoo\HexTools\Application\Analysis\ArchitectureInspector;
use Kwidoo\HexTools\Application\Analysis\BaselineRepository;
use Kwidoo\HexTools\Infrastructure\Filesystem\SourceFileScanner;
use Kwidoo\HexTools\Scanners\ClassNameResolver;
use Kwidoo\HexTools\Scanners\ModuleScanner;

afterEach(function () {
    foreach (glob(sys_get_temp_dir() . '/hex-intel-*') ?: [] as $path) {
        exec('rm -rf ' . escapeshellarg($path));
    }
});

function useArchitectureFixture($test): string
{
    $tmpApp = sys_get_temp_dir() . '/hex-intel-' . uniqid();

    foreach ([
        $tmpApp . '/Domain/Product/Entities',
        $tmpApp . '/Application/Product/UseCases',
        $tmpApp . '/Application/Product/Contracts',
        $tmpApp . '/Infrastructure/Product/Repositories',
        $tmpApp . '/Http/Controllers',
        $tmpApp . '/Domain/Order',
    ] as $dir) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($tmpApp . '/Domain/Product/Entities/Product.php', <<<'PHP'
<?php
namespace App\Domain\Product\Entities;
use Illuminate\Database\Eloquent\Model;
class Product {}
PHP);

    file_put_contents($tmpApp . '/Application/Product/UseCases/CreateProductUseCase.php', <<<'PHP'
<?php
namespace App\Application\Product\UseCases;
class CreateProductUseCase {}
PHP);

    file_put_contents($tmpApp . '/Application/Product/Contracts/ProductRepositoryContract.php', <<<'PHP'
<?php
namespace App\Application\Product\Contracts;
interface ProductRepositoryContract {}
PHP);

    file_put_contents($tmpApp . '/Infrastructure/Product/Repositories/EloquentProductRepository.php', <<<'PHP'
<?php
namespace App\Infrastructure\Product\Repositories;
class EloquentProductRepository {}
PHP);

    file_put_contents($tmpApp . '/Http/Controllers/ProductController.php', <<<'PHP'
<?php
namespace App\Http\Controllers;
class ProductController {}
PHP);

    config()->set('hex-tools.paths.app', $tmpApp);
    config()->set('hex-tools.paths.docs', dirname($tmpApp) . '/hex-docs-' . basename($tmpApp));
    config()->set('hex-tools.docs.output_path', dirname($tmpApp) . '/hex-docs-' . basename($tmpApp));
    config()->set('hex-tools.baseline.path', dirname($tmpApp) . '/hex-baseline-' . basename($tmpApp) . '.json');

    foreach ([ClassNameResolver::class, ModuleScanner::class, SourceFileScanner::class, BaselineRepository::class, ArchitectureInspector::class] as $class) {
        app()->forgetInstance($class);
    }

    return $tmpApp;
}

it('detects modules layers missing readme and forbidden dependencies', function () {
    useArchitectureFixture($this);

    $this->artisan('hex:inspect', ['--module' => 'Product'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Hex Architecture Inspection')
        ->expectsOutputToContain('Module: Product')
        ->expectsOutputToContain('[OK] Domain')
        ->expectsOutputToContain('missing_module_readme')
        ->expectsOutputToContain('domain_depends_on_framework')
        ->expectsOutputToContain('ProductRepositoryContract has no implementation detected');
});

it('supports json output and module filtering', function () {
    useArchitectureFixture($this);

    $this->artisan('hex:inspect', ['--module' => 'Product', '--format' => 'json'])
        ->assertExitCode(0)
        ->expectsOutputToContain('"name": "Product"')
        ->expectsOutputToContain('"code": "domain_depends_on_framework"')
        ->doesntExpectOutputToContain('"name": "Order"');
});

it('fails on violations when requested', function () {
    useArchitectureFixture($this);

    $this->artisan('hex:inspect', ['--module' => 'Product', '--fail-on-violations' => true])
        ->assertExitCode(1);
});

it('creates baseline and marks existing baseline issues during inspect', function () {
    useArchitectureFixture($this);
    $baseline = config('hex-tools.baseline.path');

    $this->artisan('hex:baseline', ['--output' => $baseline])
        ->assertSuccessful();

    expect(file_exists($baseline))->toBeTrue()
        ->and(file_get_contents($baseline))->toContain('"hash"');

    $this->artisan('hex:inspect', ['--module' => 'Product', '--format' => 'json'])
        ->assertExitCode(0)
        ->expectsOutputToContain('"baseline_status": "existing"');
});
