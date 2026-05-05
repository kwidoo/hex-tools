<?php

afterEach(function () {
    $docsPath = $this->app->basePath('docs/architecture-intel');
    if (is_dir($docsPath)) {
        exec('rm -rf ' . escapeshellarg($docsPath));
    }
});

it('generates architecture markdown docs', function () {
    $output = $this->app->basePath('docs/architecture-intel');

    $this->artisan('hex:docs', ['--output' => $output])
        ->assertSuccessful();

    expect(file_exists($output . '/index.md'))->toBeTrue()
        ->and(file_exists($output . '/modules.md'))->toBeTrue()
        ->and(file_exists($output . '/layers.md'))->toBeTrue()
        ->and(file_exists($output . '/dependencies.md'))->toBeTrue()
        ->and(file_exists($output . '/violations.md'))->toBeTrue()
        ->and(file_exists($output . '/baseline.md'))->toBeTrue();
});

it('does not overwrite existing docs without force', function () {
    $output = $this->app->basePath('docs/architecture-intel');
    mkdir($output, 0755, true);
    file_put_contents($output . '/index.md', 'manual');

    $this->artisan('hex:docs', ['--output' => $output])
        ->assertSuccessful();

    expect(file_get_contents($output . '/index.md'))->toBe('manual');
});

it('generates module-specific docs with module option', function () {
    $output = $this->app->basePath('docs/architecture-intel');

    $this->artisan('hex:docs', ['--output' => $output, '--module' => 'Product'])
        ->assertSuccessful();

    expect(file_exists($output . '/modules/product.md'))->toBeTrue()
        ->and(file_exists($output . '/index.md'))->toBeFalse();
});
