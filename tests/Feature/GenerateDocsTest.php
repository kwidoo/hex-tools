<?php

afterEach(function () {
    $docsPath = $this->app->basePath('docs');
    if (is_dir($docsPath)) {
        exec('rm -rf ' . escapeshellarg($docsPath));
    }
});

it('generates deptrac.md and modules.md', function () {
    $this->artisan('hex:docs:generate')->assertSuccessful();

    $docsPath = $this->app->basePath('docs/architecture');
    expect(file_exists($docsPath . '/deptrac.md'))->toBeTrue()
        ->and(file_exists($docsPath . '/modules.md'))->toBeTrue();
});

it('generates per-module docs', function () {
    $this->artisan('hex:docs:generate')->assertSuccessful();

    $docsPath = $this->app->basePath('docs/architecture');
    expect(file_exists($docsPath . '/modules/example.md'))->toBeTrue()
        ->and(file_exists($docsPath . '/modules/shared.md'))->toBeTrue();
});

it('generates docs for a single module with --module', function () {
    $this->artisan('hex:docs:generate', ['--module' => 'Example'])->assertSuccessful();

    $docsPath = $this->app->basePath('docs/architecture');
    expect(file_exists($docsPath . '/modules/example.md'))->toBeTrue()
        ->and(file_exists($docsPath . '/modules/shared.md'))->toBeFalse();
});

it('does not overwrite existing docs without --force', function () {
    $docsPath = $this->app->basePath('docs/architecture');
    mkdir($docsPath, 0755, true);
    file_put_contents($docsPath . '/deptrac.md', 'original');

    $this->artisan('hex:docs:generate')->assertSuccessful();

    expect(file_get_contents($docsPath . '/deptrac.md'))->toBe('original');
});

it('overwrites existing docs with --force', function () {
    $docsPath = $this->app->basePath('docs/architecture');
    mkdir($docsPath, 0755, true);
    file_put_contents($docsPath . '/deptrac.md', 'original');

    $this->artisan('hex:docs:generate', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($docsPath . '/deptrac.md'))->not->toBe('original');
});
