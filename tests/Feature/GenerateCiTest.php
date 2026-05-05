<?php

afterEach(function () {
    @unlink($this->app->basePath('.github/workflows/quality.yml'));
    @unlink($this->app->basePath('.drone.hex-quality.yml'));
    @unlink($this->app->basePath('custom-ci.yml'));
});

it('generates github workflow file', function () {
    $this->artisan('hex:ci:generate', ['--provider' => 'github'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('.github/workflows/quality.yml')))->toBeTrue();
});

it('generates drone ci snippet', function () {
    $this->artisan('hex:ci:generate', ['--provider' => 'drone'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('.drone.hex-quality.yml')))->toBeTrue();
});

it('uses github as the default provider', function () {
    $this->artisan('hex:ci:generate')
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('.github/workflows/quality.yml')))->toBeTrue();
});

it('does not overwrite existing github workflow without --force', function () {
    $dir = $this->app->basePath('.github/workflows');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = "{$dir}/quality.yml";
    file_put_contents($path, 'original');

    $this->artisan('hex:ci:generate', ['--provider' => 'github'])
        ->assertFailed()
        ->expectsOutputToContain('File exists');

    expect(file_get_contents($path))->toBe('original');
});

it('overwrites existing github workflow with --force', function () {
    $dir = $this->app->basePath('.github/workflows');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $path = "{$dir}/quality.yml";
    file_put_contents($path, 'original');

    $this->artisan('hex:ci:generate', ['--provider' => 'github', '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('original');
});

it('does not overwrite existing drone file without --force', function () {
    $path = $this->app->basePath('.drone.hex-quality.yml');
    file_put_contents($path, 'original');

    $this->artisan('hex:ci:generate', ['--provider' => 'drone'])
        ->assertFailed()
        ->expectsOutputToContain('File exists');

    expect(file_get_contents($path))->toBe('original');
});

it('writes to a custom output path', function () {
    $this->artisan('hex:ci:generate', ['--output' => 'custom-ci.yml'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('custom-ci.yml')))->toBeTrue();
});

it('github workflow contains required steps', function () {
    $this->artisan('hex:ci:generate', ['--provider' => 'github'])
        ->assertSuccessful();

    $content = file_get_contents($this->app->basePath('.github/workflows/quality.yml'));
    expect($content)->toContain('composer install')
        ->and($content)->toContain('php');
});

it('outputs generated path', function () {
    $this->artisan('hex:ci:generate', ['--provider' => 'github'])
        ->assertSuccessful()
        ->expectsOutputToContain('Generated');
});
