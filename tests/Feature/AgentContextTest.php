<?php

afterEach(function () {
    $base = $this->app->basePath('');
    @unlink("{$base}/AGENTS.md");
    @unlink("{$base}/CLAUDE.md");
    @unlink("{$base}/.github/copilot-instructions.md");
    @unlink("{$base}/docs/architecture/ai-context.md");
    @unlink("{$base}/docs/architecture/modules/product-ai-context.md");
});

it('generates AGENTS.md', function () {
    $this->artisan('hex:agent:context', ['--target' => 'agents'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('AGENTS.md')))->toBeTrue();
});

it('generates CLAUDE.md', function () {
    $this->artisan('hex:agent:context', ['--target' => 'claude'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('CLAUDE.md')))->toBeTrue();
});

it('generates copilot instructions', function () {
    $this->artisan('hex:agent:context', ['--target' => 'copilot'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('.github/copilot-instructions.md')))->toBeTrue();
});

it('generates docs ai-context.md', function () {
    $this->artisan('hex:agent:context', ['--target' => 'docs'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('docs/architecture/ai-context.md')))->toBeTrue();
});

it('generates all targets with --target=all', function () {
    $this->artisan('hex:agent:context', ['--target' => 'all'])
        ->assertSuccessful();

    $base = $this->app->basePath('');
    expect(file_exists("{$base}/AGENTS.md"))->toBeTrue()
        ->and(file_exists("{$base}/CLAUDE.md"))->toBeTrue()
        ->and(file_exists("{$base}/.github/copilot-instructions.md"))->toBeTrue()
        ->and(file_exists("{$base}/docs/architecture/ai-context.md"))->toBeTrue();
});

it('generates module-specific context file', function () {
    $this->artisan('hex:agent:context', ['module' => 'Product'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('docs/architecture/modules/product-ai-context.md')))->toBeTrue();
});

it('does not overwrite existing files without --force', function () {
    $path = $this->app->basePath('AGENTS.md');
    file_put_contents($path, 'original');

    $this->artisan('hex:agent:context', ['--target' => 'agents'])
        ->assertSuccessful()
        ->expectsOutputToContain('Skipped (exists)');

    expect(file_get_contents($path))->toBe('original');
});

it('overwrites existing files with --force', function () {
    $path = $this->app->basePath('AGENTS.md');
    file_put_contents($path, 'original');

    $this->artisan('hex:agent:context', ['--target' => 'agents', '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('original');
});

it('generated AGENTS.md contains architecture rules', function () {
    $this->artisan('hex:agent:context', ['--target' => 'agents'])
        ->assertSuccessful();

    $content = file_get_contents($this->app->basePath('AGENTS.md'));
    expect($content)->toContain('Domain')
        ->and($content)->toContain('Infrastructure');
});

it('generated module context contains module name', function () {
    $this->artisan('hex:agent:context', ['module' => 'Product'])
        ->assertSuccessful();

    $content = file_get_contents($this->app->basePath('docs/architecture/modules/product-ai-context.md'));
    expect($content)->toContain('Product');
});
