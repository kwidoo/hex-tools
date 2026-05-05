<?php

it('prints module map for Example', function () {
    $this->artisan('hex:map', ['module' => 'Example'])
        ->assertSuccessful()
        ->expectsOutputToContain('Example Module')
        ->expectsOutputToContain('Allowed module dependencies:');
});

it('includes scanned classes when fixture files exist', function () {
    $tmpApp = sys_get_temp_dir() . '/hex-map-test-' . uniqid();

    foreach ([
        $tmpApp . '/Domain/Example/Entities',
        $tmpApp . '/Application/Example/UseCases',
        $tmpApp . '/Http/Controllers',
        $tmpApp . '/Infrastructure/Example/Persistence',
        $tmpApp . '/Models',
    ] as $dir) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($tmpApp . '/Domain/Example/Entities/Example.php', '<?php');
    file_put_contents($tmpApp . '/Http/Controllers/ExampleController.php', '<?php');
    file_put_contents($tmpApp . '/Models/Example.php', '<?php');

    config()->set('hex-tools.paths.app', $tmpApp);
    $this->app->forgetInstance(\Kwidoo\HexTools\Config\HexToolsConfig::class);
    $this->app->forgetInstance(\Kwidoo\HexTools\Scanners\ClassNameResolver::class);
    $this->app->forgetInstance(\Kwidoo\HexTools\Scanners\ModuleScanner::class);

    $this->artisan('hex:map', ['module' => 'Example'])
        ->assertSuccessful()
        ->expectsOutputToContain('Domain:')
        ->expectsOutputToContain('Http:')
        ->expectsOutputToContain('Models:');

    exec('rm -rf ' . escapeshellarg($tmpApp));
});

it('outputs json format', function () {
    $result = $this->artisan('hex:map', ['module' => 'Example', '--format' => 'json']);
    $result->assertSuccessful();
});

it('outputs markdown format', function () {
    $this->artisan('hex:map', ['module' => 'Example', '--format' => 'markdown'])
        ->assertSuccessful()
        ->expectsOutputToContain('# Example Module');
});

it('saves output to file', function () {
    $outputFile = $this->app->basePath('example-map.txt');
    @unlink($outputFile);

    $this->artisan('hex:map', ['module' => 'Example', '--output' => 'example-map.txt'])
        ->assertSuccessful();

    expect(file_exists($outputFile))->toBeTrue();

    unlink($outputFile);
});
