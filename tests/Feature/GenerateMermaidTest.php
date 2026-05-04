<?php

afterEach(function () {
    $docsPath = $this->app->basePath('docs');
    if (is_dir($docsPath)) {
        exec('rm -rf ' . escapeshellarg($docsPath));
    }
});

it('generates module-graph.md and layer-graph.md', function () {
    $this->artisan('hex:mermaid:generate')->assertSuccessful();

    $docsPath = $this->app->basePath('docs/architecture');
    expect(file_exists($docsPath . '/module-graph.md'))->toBeTrue()
        ->and(file_exists($docsPath . '/layer-graph.md'))->toBeTrue();
});

it('module graph contains mermaid syntax with dependency edges', function () {
    $this->artisan('hex:mermaid:generate')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('docs/architecture/module-graph.md'));
    expect($content)->toContain('```mermaid')
        ->and($content)->toContain('graph TD')
        ->and($content)->toContain(' --> ');
});

it('layer graph contains mermaid syntax with dependency edges', function () {
    $this->artisan('hex:mermaid:generate')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('docs/architecture/layer-graph.md'));
    expect($content)->toContain('```mermaid')
        ->and($content)->toContain('graph TD')
        ->and($content)->toContain('Application --> Domain');
});

it('generates only modules diagram with --type=modules', function () {
    $this->artisan('hex:mermaid:generate', ['--type' => 'modules'])->assertSuccessful();

    $docsPath = $this->app->basePath('docs/architecture');
    expect(file_exists($docsPath . '/module-graph.md'))->toBeTrue()
        ->and(file_exists($docsPath . '/layer-graph.md'))->toBeFalse();
});

it('generates only layers diagram with --type=layers', function () {
    $this->artisan('hex:mermaid:generate', ['--type' => 'layers'])->assertSuccessful();

    $docsPath = $this->app->basePath('docs/architecture');
    expect(file_exists($docsPath . '/module-graph.md'))->toBeFalse()
        ->and(file_exists($docsPath . '/layer-graph.md'))->toBeTrue();
});

it('does not overwrite existing files without --force', function () {
    $docsPath = $this->app->basePath('docs/architecture');
    mkdir($docsPath, 0755, true);
    file_put_contents($docsPath . '/module-graph.md', 'original');

    $this->artisan('hex:mermaid:generate')->assertSuccessful();

    expect(file_get_contents($docsPath . '/module-graph.md'))->toBe('original');
});

it('overwrites existing files with --force', function () {
    $docsPath = $this->app->basePath('docs/architecture');
    mkdir($docsPath, 0755, true);
    file_put_contents($docsPath . '/module-graph.md', 'original');

    $this->artisan('hex:mermaid:generate', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($docsPath . '/module-graph.md'))->not->toBe('original');
});
