<?php

it('prints module map for Product', function () {
    $this->artisan('hex:map', ['module' => 'Product'])
        ->assertSuccessful()
        ->expectsOutputToContain('Product Module')
        ->expectsOutputToContain('Allowed module dependencies:');
});

it('includes scanned classes when fixture files exist', function () {
    $tmpApp = sys_get_temp_dir() . '/hex-map-test-' . uniqid();

    foreach ([
        $tmpApp . '/Domain/Product/Entities',
        $tmpApp . '/Application/Product/UseCases',
        $tmpApp . '/Http/Controllers',
        $tmpApp . '/Infrastructure/Product/Persistence',
        $tmpApp . '/Models',
    ] as $dir) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($tmpApp . '/Domain/Product/Entities/Product.php', '<?php');
    file_put_contents($tmpApp . '/Http/Controllers/ProductController.php', '<?php');
    file_put_contents($tmpApp . '/Models/Product.php', '<?php');

    config()->set('hex-tools.paths.app', $tmpApp);
    $this->app->forgetInstance(\Kwidoo\HexTools\Config\HexToolsConfig::class);
    $this->app->forgetInstance(\Kwidoo\HexTools\Scanners\ClassNameResolver::class);
    $this->app->forgetInstance(\Kwidoo\HexTools\Scanners\ModuleScanner::class);

    $this->artisan('hex:map', ['module' => 'Product'])
        ->assertSuccessful()
        ->expectsOutputToContain('Domain:')
        ->expectsOutputToContain('Http:')
        ->expectsOutputToContain('Models:');

    exec('rm -rf ' . escapeshellarg($tmpApp));
});

it('outputs json format', function () {
    $result = $this->artisan('hex:map', ['module' => 'Product', '--format' => 'json']);
    $result->assertSuccessful();
});

it('outputs markdown format', function () {
    $this->artisan('hex:map', ['module' => 'Product', '--format' => 'markdown'])
        ->assertSuccessful()
        ->expectsOutputToContain('# Product Module');
});

it('saves output to file', function () {
    $outputFile = $this->app->basePath('product-map.txt');
    @unlink($outputFile);

    $this->artisan('hex:map', ['module' => 'Product', '--output' => 'product-map.txt'])
        ->assertSuccessful();

    expect(file_exists($outputFile))->toBeTrue();

    unlink($outputFile);
});
