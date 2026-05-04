<?php

afterEach(function () {
    $appPath = $this->app->basePath('app');
    $docsPath = $this->app->basePath('docs');

    if (is_dir($appPath)) {
        exec('rm -rf ' . escapeshellarg($appPath));
    }
    if (is_dir($docsPath)) {
        exec('rm -rf ' . escapeshellarg($docsPath));
    }
});

it('creates module docs stub', function () {
    $this->artisan('hex:module:init', ['module' => 'Product'])
        ->assertSuccessful();

    $docFile = $this->app->basePath('docs/architecture/modules/product.md');
    expect(file_exists($docFile))->toBeTrue();
});

it('creates module folder skeleton with --with-folders', function () {
    $this->artisan('hex:module:init', ['module' => 'Product', '--with-folders' => true])
        ->assertSuccessful();

    $appPath = $this->app->basePath('app');
    expect(is_dir($appPath . '/Domain/Product/Entities'))->toBeTrue()
        ->and(is_dir($appPath . '/Application/Product/UseCases'))->toBeTrue()
        ->and(is_dir($appPath . '/Infrastructure/Product/Persistence'))->toBeTrue();
});

it('does not overwrite docs without --force', function () {
    $docFile = $this->app->basePath('docs/architecture/modules/product.md');
    mkdir(dirname($docFile), 0755, true);
    file_put_contents($docFile, 'original');

    $this->artisan('hex:module:init', ['module' => 'Product'])
        ->assertSuccessful();

    expect(file_get_contents($docFile))->toBe('original');
});

it('overwrites docs with --force', function () {
    $docFile = $this->app->basePath('docs/architecture/modules/product.md');
    mkdir(dirname($docFile), 0755, true);
    file_put_contents($docFile, 'original');

    $this->artisan('hex:module:init', ['module' => 'Product', '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents($docFile))->not->toBe('original');
});
