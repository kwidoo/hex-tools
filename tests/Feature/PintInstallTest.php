<?php

afterEach(function () {
    @unlink($this->app->basePath('pint.json'));
    @unlink($this->app->basePath('docs/architecture/pint.md'));
    @unlink($this->app->basePath('composer.json'));
});

it('generates pint.json', function () {
    $this->artisan('hex:pint:install')->assertSuccessful();

    expect(file_exists($this->app->basePath('pint.json')))->toBeTrue();
});

it('generates pint documentation', function () {
    $this->artisan('hex:pint:install')->assertSuccessful();

    expect(file_exists($this->app->basePath('docs/architecture/pint.md')))->toBeTrue();
});

it('does not overwrite existing pint.json without --force', function () {
    $path = $this->app->basePath('pint.json');
    file_put_contents($path, 'original');

    $this->artisan('hex:pint:install')->assertSuccessful();

    expect(file_get_contents($path))->toBe('original');
});

it('overwrites existing pint.json with --force', function () {
    $path = $this->app->basePath('pint.json');
    file_put_contents($path, 'original');

    $this->artisan('hex:pint:install', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($path))->not->toBe('original');
});

it('does not modify composer.json without --composer-scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    $original = json_encode(['name' => 'test/app']);
    file_put_contents($composerJson, $original);

    $this->artisan('hex:pint:install')->assertSuccessful();

    expect(file_get_contents($composerJson))->toBe($original);
});

it('adds pint composer scripts when --composer-scripts is passed', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode(['name' => 'test/app']));

    $this->artisan('hex:pint:install', ['--composer-scripts' => true])->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts'])->toHaveKey('fmt')
        ->and($data['scripts'])->toHaveKey('fmt:test')
        ->and($data['scripts']['fmt'])->toBe('vendor/bin/pint')
        ->and($data['scripts']['fmt:test'])->toBe('vendor/bin/pint --test');
});

it('preserves existing composer scripts when adding pint scripts', function () {
    $composerJson = $this->app->basePath('composer.json');
    file_put_contents($composerJson, json_encode([
        'name' => 'test/app',
        'scripts' => ['test' => 'pest'],
    ]));

    $this->artisan('hex:pint:install', ['--composer-scripts' => true])->assertSuccessful();

    $data = json_decode(file_get_contents($composerJson), true);
    expect($data['scripts']['test'])->toBe('pest');
});

it('shows warning when pint binary is missing', function () {
    $this->artisan('hex:pint:install')
        ->expectsOutputToContain('Pint is not installed')
        ->assertSuccessful();
});

it('generated pint.json is valid json', function () {
    $this->artisan('hex:pint:install')->assertSuccessful();

    $content = file_get_contents($this->app->basePath('pint.json'));
    $data = json_decode($content, true);

    expect($data)->not->toBeNull()
        ->and($data)->toHaveKey('preset');
});
