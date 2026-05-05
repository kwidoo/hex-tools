<?php

afterEach(function () {
    @unlink($this->app->basePath('rector.php'));
    @unlink($this->app->basePath('rector-custom.php'));
});

it('generates rector.php', function () {
    $this->artisan('hex:rector:generate')->assertSuccessful();

    expect(file_exists($this->app->basePath('rector.php')))->toBeTrue();
});

it('fails if rector.php already exists without --force', function () {
    $path = $this->app->basePath('rector.php');
    file_put_contents($path, '// original');

    $this->artisan('hex:rector:generate')->assertFailed();

    expect(file_get_contents($path))->toBe('// original');
});

it('overwrites rector.php with --force', function () {
    $path = $this->app->basePath('rector.php');
    file_put_contents($path, '// original');

    $this->artisan('hex:rector:generate', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('// original');
});

it('writes to a custom output path', function () {
    $this->artisan('hex:rector:generate', ['--output' => 'rector-custom.php'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('rector-custom.php')))->toBeTrue();
});

it('generated rector.php contains RectorConfig', function () {
    $this->artisan('hex:rector:generate')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('rector.php'));
    expect($content)->toContain('RectorConfig');
});
