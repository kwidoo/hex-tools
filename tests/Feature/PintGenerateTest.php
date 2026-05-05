<?php

afterEach(function () {
    @unlink($this->app->basePath('pint.json'));
    @unlink($this->app->basePath('pint-custom.json'));
});

it('generates pint.json', function () {
    $this->artisan('hex:pint:generate')->assertSuccessful();

    expect(file_exists($this->app->basePath('pint.json')))->toBeTrue();
});

it('fails if pint.json already exists without --force', function () {
    $path = $this->app->basePath('pint.json');
    file_put_contents($path, 'original');

    $this->artisan('hex:pint:generate')->assertFailed();

    expect(file_get_contents($path))->toBe('original');
});

it('overwrites pint.json with --force', function () {
    $path = $this->app->basePath('pint.json');
    file_put_contents($path, 'original');

    $this->artisan('hex:pint:generate', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('original');
});

it('writes to a custom output path', function () {
    $this->artisan('hex:pint:generate', ['--output' => 'pint-custom.json'])
        ->assertSuccessful();

    expect(file_exists($this->app->basePath('pint-custom.json')))->toBeTrue();
});

it('generates valid json content', function () {
    $this->artisan('hex:pint:generate')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('pint.json'));
    $data = json_decode($content, true);

    expect($data)->not->toBeNull();
});
